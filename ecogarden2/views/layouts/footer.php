<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        .footer {
            background: var(--primary-color);
            color: var(--white);
            padding: 3rem 0 1rem;
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .footer-section h4 {
            margin-bottom: 1rem;
        }
        
        .footer-section a {
            display: block;
            color: var(--white);
            text-decoration: none;
            margin-bottom: 0.5rem;
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
    </style>
    <link rel="stylesheet" href="../css/responsiveFooter.css">
</head>
<body>
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <div class="logo">
                        <i class="fas fa-leaf"></i>
                        <span>EcoGarden</span>
                    </div>
                    <p>Hanny Gissell Gomez Santiago<br>
                        Pedro Emmanuel Ovando Gonzalez<br>
                        Liliana Perez Chontal
                    </p>
                </div>
                <div class="footer-section">
                    <h4>Enlaces Rápidos</h4>
                    <a href="../views/productos/catalogo.php">Productos</a>
                    <a href="../views/clientes/login.php">Mi Cuenta</a>
                    <a href="#">Contacto</a>
                </div>
                <div class="footer-section">
                    <h4>Contacto</h4>
                    <p><i class="fas fa-envelope"></i> info@ecogarden.com</p>
                    <p><i class="fas fa-phone"></i> 922 182 8472</p>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>