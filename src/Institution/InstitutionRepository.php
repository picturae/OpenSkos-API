<?php declare(strict_types=1);

namespace App\Institution;

interface  InstitutionRepository {
    /**
     * @return Institution[]
     */
    function all() : array ;


}