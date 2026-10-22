/**
 * Local Development Server for RM-Retail POS Project (JIFFY POS)
 * Simulates Vercel Serverless environment locally
 */

const http = require('http');
const fs = require('fs');
const path = require('path');
const url = require('url');

// Load environment variables from .env or .env.local if present
function loadEnv() {
  for (const envFile of ['.env.local', '.env']) {
    const fullPath = path.join(__dirname, envFile);
    if (fs.existsSync(fullPath)) {
      const content = fs.readFileSync(fullPath, 'utf8');
      for (const line of content.split(/\r?\n/)) {
        const trimmed = line.trim();
        if (trimmed && !trimmed.startsWith('#') && trimmed.includes('=')) {
          const idx = trimmed.indexOf('=');
          const key = trimmed.substring(0, idx).trim();
          const val = trimmed.substring(idx + 1).trim().replace(/^['"](.*)['"]$/, '$1');
          if (!process.env[key]) {
            process.env[key] = val;
          }
        }
      }
    }
  }
}

loadEnv();

const apiHandler = require('./api/index.js');
const PORT = process.env.PORT || 3000;

const MIME_TYPES = {
  '.html': 'text/html; charset=utf-8',
  '.css': 'text/css; charset=utf-8',
  '.js': 'application/javascript; charset=utf-8',
  '.json': 'application/json; charset=utf-8',
  '.png': 'image/png',
  '.jpg': 'image/jpeg',
  '.svg': 'image/svg+xml',
  '.ico': 'image/x-icon'
};

const server = http.createServer(async (req, res) => {
  const parsedUrl = new URL(req.url, `http://${req.headers.host || 'localhost'}`);
  let pathname = parsedUrl.pathname;

  // 1. API Route Matching (matches /api, /api.php, /admin/api.php, /api/*)
  if (
    pathname === '/api' ||
    pathname.startsWith('/api/') ||
    pathname === '/api.php' ||
    pathname === '/admin/api.php'
  ) {
    req.query = Object.fromEntries(parsedUrl.searchParams.entries());
    return apiHandler(req, res);
  }

  // 2. Vercel Rewrite Emulation
  if (pathname === '/' || pathname === '/index.php') {
    pathname = '/index.html';
  } else if (pathname === '/project_timeline.php') {
    pathname = '/project_timeline.html';
  } else if (pathname === '/admin' || pathname === '/admin/' || pathname === '/admin/index.php') {
    pathname = '/admin/index.html';
  } else if (pathname === '/admin/login' || pathname === '/admin/login.php') {
    pathname = '/admin/login.html';
  }

  // 3. Static File Serving
  let filePath = path.join(__dirname, pathname);

  // Security check: ensure path is within directory
  if (!filePath.startsWith(__dirname)) {
    res.statusCode = 403;
    res.end('Access Denied');
    return;
  }

  fs.stat(filePath, (err, stats) => {
    if (err || !stats.isFile()) {
      // Check if folder contains index.html
      if (stats && stats.isDirectory()) {
        const indexPath = path.join(filePath, 'index.html');
        if (fs.existsSync(indexPath)) {
          filePath = indexPath;
        } else {
          res.statusCode = 404;
          res.end('Not Found');
          return;
        }
      } else {
        res.statusCode = 404;
        res.end('Not Found');
        return;
      }
    }

    const ext = path.extname(filePath).toLowerCase();
    const contentType = MIME_TYPES[ext] || 'application/octet-stream';

    res.setHeader('Content-Type', contentType);
    fs.createReadStream(filePath).pipe(res);
  });
});

server.listen(PORT, () => {
  console.log(`====================================================`);
  console.log(`🚀 RM-Retail POS Project (JIFFY POS) Server running!`);
  console.log(`📡 Local URL: http://localhost:${PORT}`);
  console.log(`📂 Admin URL: http://localhost:${PORT}/admin/`);
  console.log(`⚙️  Supabase URL: ${process.env.SUPABASE_URL || '(Not set yet - check .env)'}`);
  console.log(`====================================================`);
});
