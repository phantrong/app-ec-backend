<?php

function isSameFloat($float1, $float2)
{
    if (abs(($float1 - $float2) / $float2) < 0.00001) {
        return true;
    }
    return false;
}
