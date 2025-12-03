<?php
class CuponController {
    private $conexion;
    
    public function __construct($conexion) {
        $this->conexion = $conexion;
    }
    
    public function validarCupon($codigo, $subtotal) {
        $codigo = mysqli_real_escape_string($this->conexion, $codigo);
        
        $sql = "SELECT * FROM cupones_descuento 
                WHERE codigo = '$codigo' 
                AND activo = 1 
                AND (fecha_inicio IS NULL OR fecha_inicio <= CURDATE())
                AND (fecha_fin IS NULL OR fecha_fin >= CURDATE())
                AND (usos_maximos IS NULL OR usos_actuales < usos_maximos)";
        
        $result = mysqli_query($this->conexion, $sql);
        
        if (!$result || mysqli_num_rows($result) == 0) {
            return ['error' => 'Cupón no válido o expirado'];
        }
        
        $cupon = mysqli_fetch_assoc($result);
        
        //verificar minimo de compra
        if ($subtotal < $cupon['min_compra']) {
            $minimo = number_format($cupon['min_compra'], 2);
            return ['error' => "Mínimo de compra: $$minimo"];
        }
        
        return ['success' => true, 'cupon' => $cupon];
    }
    
    public function calcularDescuento($cupon, $subtotal) {
        if ($cupon['tipo'] == 'porcentaje') {
            return $subtotal * ($cupon['valor'] / 100);
        } else { //monto_fijo
            return min($cupon['valor'], $subtotal);
        }
    }
    
    public function registrarUso($cupon_id) {
        $sql = "UPDATE cupones_descuento 
                SET usos_actuales = usos_actuales + 1 
                WHERE id = $cupon_id";
        return mysqli_query($this->conexion, $sql);
    }
}
?>