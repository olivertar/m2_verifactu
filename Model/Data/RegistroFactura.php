<?php
/**
 * Orangecat Verifactuapi RegistroFactura Data Object
 *
 * @category  Orangecat
 * @package   Orangecat_Verifactuapi
 */

namespace Orangecat\Verifactuapi\Model\Data;

class RegistroFactura
{
    /**
     * @var Emisor
     */
    private $emisor;
    
    /**
     * @var Destinatario|null
     */
    private $destinatario;
    
    /**
     * @var Desglose[]
     */
    private $desgloses = [];
    
    /**
     * @var string
     */
    private $numSerieFactura;
    
    /**
     * @var string
     */
    private $fechaExpedicion;
    
    /**
     * @var string|null
     */
    private $refExterna;
    
    /**
     * @var string
     */
    private $tipoFactura;
    
    /**
     * @var string
     */
    private $descripcionOperacion;
    
    /**
     * @var float
     */
    private $cuotaTotal;
    
    /**
     * @var float
     */
    private $importeTotal;
    
    /**
     * @param Emisor $emisor
     */
    public function __construct(Emisor $emisor)
    {
        $this->emisor = $emisor;
        $this->tipoFactura = 'F2'; // Default: simplified
        $this->descripcionOperacion = 'Venta de productos/servicios';
    }
    
    /**
     * Set destinatario
     *
     * @param Destinatario|null $destinatario
     * @return $this
     */
    public function setDestinatario($destinatario)
    {
        $this->destinatario = $destinatario;
        if ($destinatario) {
            $this->tipoFactura = 'F1'; // Complete invoice with recipient
        }
        return $this;
    }
    
    /**
     * Add desglose
     *
     * @param Desglose $desglose
     * @return $this
     */
    public function addDesglose(Desglose $desglose)
    {
        $this->desgloses[] = $desglose;
        return $this;
    }
    
    /**
     * Set invoice details
     *
     * @param string $numSerie
     * @param string $fecha
     * @param string|null $refExterna
     * @return $this
     */
    public function setInvoiceDetails($numSerie, $fecha, $refExterna = null)
    {
        $this->numSerieFactura = $numSerie;
        $this->fechaExpedicion = $fecha;
        $this->refExterna = $refExterna;
        return $this;
    }
    
    /**
     * Set description
     *
     * @param string $descripcion
     * @return $this
     */
    public function setDescripcion($descripcion)
    {
        $this->descripcionOperacion = $descripcion;
        return $this;
    }
    
    /**
     * Set totals
     *
     * @param float $cuotaTotal
     * @param float $importeTotal
     * @return $this
     */
    public function setTotals($cuotaTotal, $importeTotal)
    {
        $this->cuotaTotal = (float) $cuotaTotal;
        $this->importeTotal = (float) $importeTotal;
        return $this;
    }
    
    /**
     * Convert to array for API request
     *
     * @return array
     */
    public function toArray()
    {
        // Emisor data
        $emisorData = $this->emisor->toArray();
        
        // Use API naming convention (PascalCase)
        $data = [
            'IDEmisorFactura' => $emisorData['nif'], // NIF, not numeric ID
            'NombreRazonEmisor' => $emisorData['nombre'],
            'NumSerieFactura' => $this->numSerieFactura,
            'FechaExpedicionFactura' => $this->fechaExpedicion,
            'TipoFactura' => $this->tipoFactura,
            'DescripcionOperacion' => $this->descripcionOperacion,
            'CuotaTotal' => $this->cuotaTotal,
            'ImporteTotal' => $this->importeTotal
        ];
        
        // Add desgloses as array (API expects this format)
        if (!empty($this->desgloses)) {
            $data['Desglose'] = array_map(function($d) { 
                return $d->toArray(); 
            }, $this->desgloses);
        }
        
        // Add destinatario if exists
        if ($this->destinatario) {
            $destinatarioData = $this->destinatario->toArray();
            $data['Destinatarios'] = [[
                'NIF' => $destinatarioData['nif'],
                'NombreRazon' => $destinatarioData['nombreRazon']
            ]];
        } else {
            // Si no hay destinatario, es factura simplificada
            $data['FacturaSinIdentifDestArt61d'] = 'S';
        }
        
        if ($this->refExterna) {
            $data['RefExterna'] = $this->refExterna;
        }
        
        return $data;
    }
    
    /**
     * Get total number of desgloses
     *
     * @return int
     */
    public function getDesglosesCount()
    {
        return count($this->desgloses);
    }
}
