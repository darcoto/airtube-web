<?php

function my_warning_handler($errno, $errstr,$errfile,$errline) {
  if(error_reporting()){
    throw new WarningFault($errstr,$errno,$errfile,$errline);
  }
}

//------------------------------------------------------------------------------
class WarningFault extends Exception{
  public function __construct($message, $code,$file,$line) {
    $this->file = $file;
    $this->line = $line;
    $this->is_fatal_error = 1;
    parent::__construct($message, $code);
  }
}