<?php

require_once "library/Converter.php";

$converter = new Converter();
$converter->AddFile("same1.aif");
$converter->AddFile("same2.aif");
$converter->Submit();
