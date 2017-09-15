<?php

// Grava arquivos de log das transacoes
function gravar_arquivo($file_name, $var)
{
    //$content = serialize($var);
    $content = $var;
    $fd = @fopen($file_name, 'w+');
    fwrite($fd, $content);
    fclose($fd);
    chmod($file_name, 0644);
    return true;
};


// Ler conteúdo de um arquivo
function ler_arquivo($file_name)
{    
    $ponteiro = fopen("$file_name", 'r');
    $file     = fgets($ponteiro, 4096);
    fclose($ponteiro);
    return $file;
    /*
    $handle = fopen($file_name, "r");
    $contents = fread($handle, filesize($file_name));
    fclose($handle);
    return true;
    */
};


// Retornar username da URI enviada pelo cliente Android
function username ($this, $inthat)
{
    if (!is_bool(strpos($inthat, $this)))
    return substr($inthat, strpos($inthat,$this)+strlen($this));
};


// Funções para quebrar strings
    function after ($this, $inthat)
    {
        if (!is_bool(strpos($inthat, $this)))
        return substr($inthat, strpos($inthat,$this)+strlen($this));
    };

    function after_last ($this, $inthat)
    {
        if (!is_bool(strrevpos($inthat, $this)))
        return substr($inthat, strrevpos($inthat, $this)+strlen($this));
    };

    function before ($this, $inthat)
    {
        return substr($inthat, 0, strpos($inthat, $this));
    };

    function before_last ($this, $inthat)
    {
        return substr($inthat, 0, strrevpos($inthat, $this));
    };

    function between ($this, $that, $inthat)
    {
        return before ($that, after($this, $inthat));
    };

    function between_last ($this, $that, $inthat)
    {
     return after_last($this, before_last($that, $inthat));
    };

?>
