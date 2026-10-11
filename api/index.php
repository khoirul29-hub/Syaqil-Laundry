<?php
declare(strict_types=1);
// Vercel Serverless entry point (vercel-php runtime). Semua request /api/*
// di-rewrite ke sini; server/api.php me-routing berdasarkan REQUEST_URI.
require __DIR__.'/../server/api.php';
