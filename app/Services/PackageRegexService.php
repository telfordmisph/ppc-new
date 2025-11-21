<?php

class PackageRegexService
{
  public function extractDimension($text)
  {
    $pattern = '/((\d+(\.\d+)?|\.\d+)([xX](\d+(\.\d+)?|\.\d+)){1,2}|\d+([Mm][Ii][Ll][Ss]))/';
    if (preg_match($pattern, $text, $matches)) {
      return $matches[0];
    }
    return null;
  }

  public function extractLeadCount($text)
  {
    $pattern = '/\d*\.\d+|\d+/';
    if (preg_match($pattern, $text, $matches)) {
      return $matches[0];
    }
    return null;
  }
}
