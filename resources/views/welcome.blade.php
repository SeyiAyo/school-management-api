<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>School Management API</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
            padding: 0;
            margin: 0;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        header {
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 1.5rem 0;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: #4f46e5;
        }

        main {
            padding: 3rem 0;
        }

        .hero {
            text-align: center;
            margin-bottom: 3rem;
        }

        h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #1f2937;
        }

        .subtitle {
            font-size: 1.25rem;
            color: #6b7280;
            margin-bottom: 2rem;
        }

        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card h2 {
            font-size: 1.25rem;
            margin-bottom: 1rem;
            color: #4f46e5;
        }

        .card p {
            color: #6b7280;
            margin-bottom: 1rem;
        }

        .api-endpoints {
            background-color: #f3f4f6;
            border-radius: 8px;
            padding: 2rem;
            margin-top: 3rem;
        }

        .api-endpoints h2 {
            margin-bottom: 1rem;
            color: #1f2937;
        }

        .endpoint {
            background-color: #fff;
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid #4f46e5;
        }

        .endpoint-method {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            background-color: #4f46e5;
            color: white;
            border-radius: 4px;
            font-size: 0.875rem;
            margin-right: 0.5rem;
        }

        .endpoint-url {
            font-family: monospace;
            color: #1f2937;
        }

        .endpoint-description {
            margin-top: 0.5rem;
            color: #6b7280;
        }

        footer {
            background-color: #1f2937;
            color: #f9fafb;
            padding: 2rem 0;
            margin-top: 3rem;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .footer-links {
            display: flex;
            gap: 1.5rem;
        }

        .footer-links a {
            color: #e5e7eb;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: #fff;
        }

        @media (max-width: 768px) {
            .card-grid {
                grid-template-columns: 1fr;
            }

            .footer-content {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container header-content">
            <div class="logo">School Management API</div>
        </div>
    </header>

    <main class="container">
        <section class="hero">
            <h1>Welcome to the School Management API</h1>
            <p class="subtitle">A comprehensive API for managing school operations, students, staff, and resources</p>
        </section>

        <div class="card-grid">
            <div class="card">
                <h2>API Documentation</h2>
                <p>Explore our comprehensive API documentation to learn how to integrate with our system.</p>
                <p>Coming soon...</p>
            </div>

            <div class="card">
                <h2>Authentication</h2>
                <p>Our API uses secure authentication to protect your data. Learn how to authenticate your requests.</p>
                <p>Coming soon...</p>
            </div>

            <div class="card">
                <h2>Resources</h2>
                <p>Discover the various resources available through our API and how to use them effectively.</p>
                <p>Coming soon...</p>
            </div>
        </div>

    </main>

    <footer>
        <div class="container footer-content">
            <div>
                <p>&copy; {{ date('Y') }} School Management API. All rights reserved.</p>
            </div>
            <div class="footer-links">
                <a href="https://github.com/SeyiAyo/school-management-api" target="_blank">GitHub</a>
                <a href="#">Documentation</a>
                <a href="#">Support</a>
            </div>
        </div>
    </footer>
</body>
</html>
