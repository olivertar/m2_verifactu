<?php
/**
 * Orangecat Verifactuapi Emisor Data Object
 *
 * @category  Orangecat
 * @package   Orangecat_Verifactuapi
 */

namespace Orangecat\Verifactuapi\Model\Data;

class Emisor
{
    /**
     * @var string
     */
    private $nif;
    
    /**
     * @var string
     */
    private $nombre;
    
    /**
     * @param string $nif
     * @param string $nombre
     */
    public function __construct($nif, $nombre)
    {
        $this->nif = $nif;
        $this->nombre = $nombre;
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
            'nombre' => $this->nombre
        ];
    }
}
