<?php
/**
 * Orangecat Verifactuapi Desglose Data Object
 *
 * @category  Orangecat
 * @package   Orangecat_Verifactuapi
 */

namespace Orangecat\Verifactuapi\Model\Data;

class Desglose
{
    /**
     * @var int
     */
    private $impuesto;
    
    /**
     * @var int
     */
    private $claveRegimen;
    
    /**
     * @var int
     */
    private $calificacionOperacion;
    
    /**
     * @var float
     */
    private $tipoImpositivo;
    
    /**
     * @var float
     */
    private $baseImponible;
    
    /**
     * @var float
     */
    private $cuotaRepercutida;
    
    /**
     * @param float $tipoImpositivo
     * @param float $baseImponible
     * @param float $cuotaRepercutida
     * @param int $impuesto
     * @param int $claveRegimen
     * @param int $calificacionOperacion
     */
    public function __construct(
        $tipoImpositivo,
        $baseImponible,
        $cuotaRepercutida,
        $impuesto = 1,              // 1 = IVA
        $claveRegimen = 1,          // 1 = General
        $calificacionOperacion = 1  // 1 = S1 - Sujeta
    ) {
        $this->tipoImpositivo = (float) $tipoImpositivo;
        $this->baseImponible = (float) $baseImponible;
        $this->cuotaRepercutida = (float) $cuotaRepercutida;
        $this->impuesto = (int) $impuesto;
        $this->claveRegimen = (int) $claveRegimen;
        $this->calificacionOperacion = (int) $calificacionOperacion;
    }
    
    /**
     * Convert to array for API request
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'Impuesto' => $this->impuesto,
            'ClaveRegimen' => $this->claveRegimen,
            'CalificacionOperacion' => $this->calificacionOperacion,
            'TipoImpositivo' => $this->tipoImpositivo,
            'BaseImponibleOImporteNoSujeto' => $this->baseImponible,
            'CuotaRepercutida' => $this->cuotaRepercutida
        ];
    }
    
    /**
     * Get tipo impositivo
     *
     * @return float
     */
    public function getTipoImpositivo()
    {
        return $this->tipoImpositivo;
    }
    
    /**
     * Get base imponible
     *
     * @return float
     */
    public function getBaseImponible()
    {
        return $this->baseImponible;
    }
    
    /**
     * Get cuota repercutida
     *
     * @return float
     */
    public function getCuotaRepercutida()
    {
        return $this->cuotaRepercutida;
    }
}
