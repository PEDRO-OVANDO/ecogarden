<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoGarden - Jardinería Urbana</title>
    <style>
        :root {
            --primary-color: #2d5a27;
            --secondary-color: #4CAF50;
            --accent-color: #ff6b35;
            --text-color: #333;
            --light-bg: #f8f9fa;
            --white: #ffffff;
            --gray: #6c757d;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Century Gothic', Arial, sans-serif; background: var(--light-bg);
            line-height: 1.6;
            color: var(--text-color);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /*seccion inicial*/
        .hero {
            display: grid;
            grid-template-columns: 1fr 1fr;
            align-items: center;
            gap: 3rem;
            padding: 120px 0 60px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 80vh;
        }
        
        .hero-content {
            padding: 2rem;
        }
        
        .hero-title {
            font-size: 3rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .hero-subtitle {
            font-size: 1.2rem;
            color: var(--gray);
            margin-bottom: 2rem;
        }
        
        .hero-buttons {
            display: flex;
            gap: 1rem;
        }
        
        .hero-image {
            text-align: center;
            padding: 2rem;
        }
        
        .hero-image img {
            max-width: 100%;
            height: auto;
        }
        
        /*botones*/
        .btn {
            display: inline-block;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: var(--white);
        }
        
        .btn-primary:hover {
            background: #23421f;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }
        
        .btn-secondary:hover {
            background: var(--primary-color);
            color: var(--white);
        }
        
        /*seccion porque elegir ecogarden*/
        .features {
            padding: 80px 0;
            background: var(--white);
        }
        
        .section-title {
            text-align: center;
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 3rem;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }
        
        .feature-card {
            text-align: center;
            padding: 2rem;
            border-radius: 10px;
            background: var(--light-bg);
            transition: transform 0.3s;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
        }
        
        .feature-icon {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        /*seccion categorias*/
        .categories {
            padding: 80px 0;
            background: var(--light-bg);
        }
        
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }
        
        .category-card {
            background: var(--white);
            padding: 2rem;
            border-radius: 10px;
            text-align: center;
            text-decoration: none;
            color: var(--text-color);
            transition: all 0.3s;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.2);
        }
        
        .category-icon {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .hero {
                grid-template-columns: 1fr;
                text-align: center;
                padding: 100px 0 40px;
            }
            
            .hero-title {
                font-size: 2rem;
            }
            
            .nav-links {
                gap: 1rem;
            }
        }
    </style>
    <link rel="stylesheet" href="views/css/responsiveIndex.css">
</head>
<body>
    <?php include 'views/layouts/header.php'; ?>
    
    <!--seccion inicial-->
    <section class="hero">
        <div class="hero-content">
            <h1 class="hero-title">Cultiva tu espacio, cultiva tu vida</h1>
            <p class="hero-subtitle">Todo lo que necesitas para tu jardín urbano en un solo lugar</p>
            <div class="hero-buttons">
                <a href="../views/productos/catalogo.php" class="btn btn-primary">Explorar Productos</a>
                <a href="../views/clientes/registro.php" class="btn btn-secondary">Crear Cuenta</a>
            </div>
        </div>
        <div class="hero-image">
            <!--reemplazarla despues-->
            <div >
                <img src="other/img/magnolia.jpg" style="width: 400px; height: 300px; border-radius: 10px; object-fit: cover;">
            </div>
        </div>
    </section>

    <!--seccion porque elegir ecogarden-->
    <section class="features">
        <div class="container">
            <h2 class="section-title">¿Por qué elegir EcoGarden?</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-seedling"></i>
                    </div>
                    <h3>Kits Completos</h3>
                    <p>Todo lo necesario para empezar a cultivar en un solo paquete</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-tools"></i>
                    </div>
                    <h3>Herramientas Profesionales</h3>
                    <p>Calidad garantizada para el cuidado de tus plantas</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shipping-fast"></i>
                    </div>
                    <h3>Envío Rápido</h3>
                    <p>Recibe tus productos en la comodidad de tu hogar</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h3>Soporte Expertos</h3>
                    <p>Asesoría especializada en jardinería urbana</p>
                </div>
            </div>
        </div>
    </section>

    <!--seccion categorias-->
    <section class="categories">
        <div class="container">
            <h2 class="section-title">Nuestras Categorías</h2>
            <div class="categories-grid">
                <a href="views/productos/catalogo.php?categoria=kits" class="category-card">
                    <div class="category-icon">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <h3>Kits Todo-en-Uno</h3>
                    <p>Comienza fácilmente con nuestros kits completos</p>
                </a>
                <a href="views/productos/catalogo.php?categoria=herramientas" class="category-card">
                    <div class="category-icon">
                        <i class="fas fa-toolbox"></i>
                    </div>
                    <h3>Herramientas</h3>
                    <p>Equipos profesionales para el cuidado</p>
                </a>
                <a href="views/productos/catalogo.php?categoria=insumos" class="category-card">
                    <div class="category-icon">
                        <i class="fas fa-vial"></i>
                    </div>
                    <h3>Insumos</h3>
                    <p>Sustratos, fertilizantes y semillas</p>
                </a>
                <a href="views/productos/catalogo.php?categoria=macetas" class="category-card">
                    <div class="feature-icon">
                        <i class="fas fa-mortar-pestle"></i>
                    </div>
                    <h3>Macetas</h3>
                    <p>Diseños para todos los espacios</p>
                </a>
            </div>
        </div>
    </section>

    <?php include 'views/layouts/footer.php'; ?>

    <!-- Font Awesome para íconos -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    
    <script>
        // JavaScript básico
        document.addEventListener('DOMContentLoaded', function() {
            console.log('EcoGarden - Página cargada correctamente');
        });
    </script>
</body>
</html>