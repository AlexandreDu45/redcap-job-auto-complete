<?php

namespace STPH\addressAutoComplete;

if (!class_exists("source")) {
    require_once(__DIR__ . "/../classes/source.class.php");
}

class SOCcerNET extends source
{
    public function mapAddress($value)
    {
        $address = new Address;

        $address->label = $value->title;
        $address->value = $value->title;

        // Champs avancés pour ajouter automatiquement street (libéllé) et le number (le code à 5 chiffres)
        $address->parts->street = $value->score;
        $address->parts->number = $value->code;
        $address->parts->keyword = $value->searchTerm;

        return $address;
    }
}