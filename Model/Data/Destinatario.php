<?php
/**
 * Orangecat Verifactuapi Destinatario Data Object
 *
 * @category  Orangecat
 * @package   Orangecat_Verifactuapi
 */

namespace Orangecat\Verifactuapi\Model\Data;

class Destinatario
{
    /**
     * @var string
     */
    private $nif;
    
    /**
     * @var string
     */
    private $nombreRazon;
    
    /**
     * @param string $nif
     * @param string $nombreRazon
     */
    public function __construct($nif, $nombreRazon)
    {
        $this->nif = $nif;
        $this->nombreRazon = $nombreRazon;
    }
    
    /**
     * Convert to array for API request
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'nif' => $this->nif,
            'nombreRazon' => $this->nombreRazon
        ];
    }
}
