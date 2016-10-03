<?php
namespace IdCultura\IdCulturaBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class IdCulturaBundle extends Bundle
{
    public function getParent()
    {
        return 'LoginCidadaoCoreBundle';
    }
}
