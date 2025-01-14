<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paperwork Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
        }

        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background: url('login-bg.png') no-repeat center center;
            background-size: cover;
            opacity: 0.1;
            z-index: 0;
        }

        .hero-content {
            position: relative;
            z-index: 1;
            padding: 6rem 0;
        }

        /* Feature Cards */
        .feature-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 2rem;
            transition: transform 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
            color: white;
        }

        /* Action Buttons */
        .btn-modern {
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-modern.btn-light {
            background: rgba(255, 255, 255, 0.9);
            border: none;
            color: var(--primary-color);
        }

        .btn-modern.btn-outline-light {
            border: 2px solid rgba(255, 255, 255, 0.9);
            color: white;
        }

        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .hero-content {
                padding: 4rem 0;
            }
            .feature-card {
                margin-bottom: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="hero-section">
        <div class="hero-content">
            <div class="container">
                <!-- Header -->
                <div class="text-center text-white mb-5">
                    <h1 class="display-4 fw-bold mb-3">Paperwork Management System</h1>
                    <p class="lead mb-5">Streamline your academic paperwork process with our modern digital solution</p>
                    <div class="d-flex justify-content-center gap-3">
                        <a href="login.php" class="btn btn-modern btn-light">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </a>
                        <a href="register.php" class="btn btn-modern btn-outline-light">
                            <i class="fas fa-user-plus me-2"></i>Register
                        </a>
                    </div>
                </div>

                <!-- Features Grid -->
                <div class="row g-4">
                    <!-- Feature 1: Digital Submissions -->
                    <div class="col-md-4">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-file-upload"></i>
                            </div>
                            <h5 class="text-white mb-3">Digital Submissions</h5>
                            <p class="text-white-50 mb-0">Submit and manage all your paperwork digitally, eliminating the need for physical documents.</p>
                        </div>
                    </div>

                    <!-- Feature 2: Real-time Tracking -->
                    <div class="col-md-4">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <h5 class="text-white mb-3">Real-time Tracking</h5>
                            <p class="text-white-50 mb-0">Monitor your submission status in real-time with comprehensive tracking features.</p>
                        </div>
                    </div>

                    <!-- Feature 3: Secure Storage -->
                    <div class="col-md-4">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <h5 class="text-white mb-3">Secure Storage</h5>
                            <p class="text-white-50 mb-0">All documents are securely stored with enterprise-grade encryption and protection.</p>
                        </div>
                    </div>

                    <!-- Feature 4: Automated Workflow -->
                    <div class="col-md-4">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-cogs"></i>
                            </div>
                            <h5 class="text-white mb-3">Automated Workflow</h5>
                            <p class="text-white-50 mb-0">Streamlined approval process with automated notifications and reminders.</p>
                        </div>
                    </div>

                    <!-- Feature 5: Role-based Access -->
                    <div class="col-md-4">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-users-cog"></i>
                            </div>
                            <h5 class="text-white mb-3">Role-based Access</h5>
                            <p class="text-white-50 mb-0">Secure access control with different permission levels for staff, HOD, and dean.</p>
                        </div>
                    </div>

                    <!-- Feature 6: Analytics -->
                    <div class="col-md-4">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-chart-pie"></i>
                            </div>
                            <h5 class="text-white mb-3">Analytics Dashboard</h5>
                            <p class="text-white-50 mb-0">Comprehensive analytics and reporting tools for better decision making.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>