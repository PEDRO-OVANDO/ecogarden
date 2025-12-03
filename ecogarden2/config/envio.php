<?php
class EnvioController {
    private $conexion;
    
    public function __construct($conexion) {
        $this->conexion = $conexion;
    }
    
    public function calcularEnvio($subtotal) {
        return $this->obtenerTarifaPorPrecio($subtotal) ?: $this->obtenerTarifaDefault();
    }
    
    private function obtenerTarifaPorPrecio($subtotal) {
        $sql = "SELECT * FROM tarifas_envio 
                WHERE tipo = 'precio' 
                AND activo = 1 
                AND rango_min <= $subtotal 
                AND (rango_max IS NULL OR rango_max >= $subtotal)
                ORDER BY costo ASC 
                LIMIT 1";
        
        $result = mysqli_query($this->conexion, $sql);
        return $result && mysqli_num_rows($result) > 0 ? mysqli_fetch_assoc($result) : null;
    }
    
    private function obtenerTarifaDefault() {
        // Tarifa por defecto (envío básico)
        return [
            'id' => 0,
            'nombre' => 'Envío Estándar',
            'costo' => 10.00,
            'dias_entrega' => 5,
            'tipo' => 'precio'
        ];
    }
    
    public function obtenerTodasTarifas() {
        $sql = "SELECT * FROM tarifas_envio WHERE activo = 1 ORDER BY tipo, rango_min";
        $result = mysqli_query($this->conexion, $sql);
        
        $tarifas = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $tarifas[] = $row;
            }
        }
        
        return $tarifas;
    }
}
?>