<?php
function AdresseIPValideReseau($adresse) {
    $regex = '/^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.' .  // w: 1-255
             '(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.' .      // x: 0-255
             '(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.' .      // y: 0-255
             '0$/';                                            // z: 0

    return (bool) preg_match($regex, $adresse);
}

function AdresseIPValideSousReseau($adressereseau, $mask, $adressesousreseau) {

    if (!filter_var($adressesousreseau, FILTER_VALIDATE_IP)) {
        return false;
    }

    $adresseReseauLong = ip2long($adressereseau);
    $maskLong = ip2long($mask);
    $adresseSousReseauLong = ip2long($adressesousreseau);
    
    $networkLong = $adresseReseauLong & $maskLong;
    $subnetLong = $adresseSousReseauLong & $maskLong;
    
    return $networkLong === $subnetLong;
}

function AdresseIPValidePC($adressePC, $adressesousreseau, $mask) {
    if (!filter_var($adressePC, FILTER_VALIDATE_IP)) {
        return false;  
    }

    $pcLong = ip2long($adressePC);
    $subnetLong = ip2long($adressesousreseau);
    $maskLong = ip2long($mask);

    if (($pcLong & $maskLong) !== ($subnetLong & $maskLong)) {
        return false;  
    }

    return true;  
}


function masqueValide($masque) {
    $long = ip2long($masque);
    if ($long === false) {
        return false; 
    }

    $binaryMask = decbin($long);

    $firstZero = strpos($binaryMask, '0');

    if ($firstZero !== false) {
        for ($i = $firstZero; $i < strlen($binaryMask); $i++) {
            if ($binaryMask[$i] !== '0') {
                return false; 
            }
        }
    }

    return true;
}


?>

