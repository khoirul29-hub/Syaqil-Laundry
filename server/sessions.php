<?php
declare(strict_types=1);
// Session PHP yang disimpan di database, bukan di file.
//
// Di serverless (Vercel) filesystem tiap invocation bersifat ephemeral dan
// request bisa jatuh ke instance berbeda, sehingga session file bawaan PHP
// tidak bisa diandalkan (login/CSRF akan hilang acak). Handler ini menyimpan
// session di tabel `sessions` sehingga tetap konsisten di semua instance.
// Di hosting biasa (php -S / Apache) perilakunya sama persis.
function useDbSessions(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $handler = new SyaqilDbSessionHandler();
    if ($handler->available()) session_set_save_handler($handler);
}

final class SyaqilDbSessionHandler implements SessionHandlerInterface {
    public function available(): bool {
        try {
            db()->exec('CREATE TABLE IF NOT EXISTS sessions (id VARCHAR(128) PRIMARY KEY, data MEDIUMTEXT NOT NULL, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP)');
            db()->query('SELECT 1 FROM sessions LIMIT 1');
            return true;
        } catch (Throwable) {
            return false;
        }
    }
    private function lifetime(): int {
        $ttl = (int)ini_get('session.gc_maxlifetime');
        return $ttl > 0 ? $ttl : 1440;
    }
    public function open(string $path, string $name): bool { return true; }
    public function close(): bool { return true; }
    public function read(string $id): string|false {
        try {
            $row = db()->prepare('SELECT data FROM sessions WHERE id=? AND updated_at > NOW() - INTERVAL '.$this->lifetime().' SECOND');
            $row->execute([$id]);
            $data = $row->fetchColumn();
            return is_string($data) ? $data : '';
        } catch (Throwable) {
            return '';
        }
    }
    public function write(string $id, string $data): bool {
        try {
            $q = db()->prepare('REPLACE INTO sessions(id,data,updated_at) VALUES(?,?,NOW())');
            return $q->execute([$id, $data]);
        } catch (Throwable) {
            return false;
        }
    }
    public function destroy(string $id): bool {
        try {
            $q = db()->prepare('DELETE FROM sessions WHERE id=?');
            return $q->execute([$id]);
        } catch (Throwable) {
            return false;
        }
    }
    public function gc(int $max_lifetime): int|false {
        try {
            $q = db()->prepare('DELETE FROM sessions WHERE updated_at < NOW() - INTERVAL '.$max_lifetime.' SECOND');
            $q->execute();
            return $q->rowCount();
        } catch (Throwable) {
            return false;
        }
    }
}
