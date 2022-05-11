<?php

namespace ermine;

class geoloc {
    
    /**
     * Fonction faite à l'arrache pour sauvegarde du code
     * @param float $centerLat
     * @param float $centerLng
     * @return string
     * @todo tester et debuger
     */
    function drawCircle($centerLat, $centerLng) {
        $zone = null;

        if ($centerLat && $centerLng) {
            $distanceLat = 0.008286;    // correspond
            $distanceLng = 0.012393;
            $tabZone = [];
            for ($i=0; $i<60; $i++) {
                $angle = 6.2831 * $i / 60;
                $lat = round($centerLat + cos($angle) * $distanceLat, 5);
                $lng = round($centerLng - sin($angle) * $distanceLng, 5);
                $tabZone[] = $lng.' '.$lat;
            }
            
            // le polygon doit être fermé
            if ($tabZone[0] != $tabZone[count($tabZone)-1]) {
                $tabZone[] = $tabZone[0];
            }

            $zone = implode(',', $tabZone);
        }
        
        return $zone;
    }
}
