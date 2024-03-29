<?php

namespace FicheHeure\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SaisieHeureLibelle
 *
 * @ORM\Table(name="saisie_heure_libelle")
 * @ORM\Entity
 */
class SaisieHeureLibelle
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="intitule_libelle", type="string", length=50, precision=0, scale=0, nullable=false, unique=false)
     */
    private $intituleLibelle;


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set intituleLibelle
     *
     * @param string $intituleLibelle
     * @return SaisieHeureLibelle
     */
    public function setIntituleLibelle($intituleLibelle)
    {
        $this->intituleLibelle = $intituleLibelle;
    
        return $this;
    }

    /**
     * Get intituleLibelle
     *
     * @return string 
     */
    public function getIntituleLibelle()
    {
        return $this->intituleLibelle;
    }
}
