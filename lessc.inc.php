<?php

class lessc
{
    public  $importDir = '';
    private $formatterName;
    
    public function addImportDir($dir)
    {
        $this->importDir = (array)$this->importDir;
        $this->importDir[] = $dir;
    }
    
    public function setFormatter($name)
    {
        $this->formatterName = $name;
    }
    
    public function setPreserveComments($preserve)
    {}
    
    public function parse($buffer)
    {
        $options = array();
        
        $dirs_ = (array)$this->importDir;
        
        $dirs = array();
        
        foreach($dirs_ as $dir)
        {
            $dirs[$dir] = '/';
        }
        
        switch($this->formatterName)
        {
            case 'compressed':
                $options['compress'] = true;
                break;
        }
        
        $parser = new Less_Parser($options);
        $parser->SetImportDirs($dirs);
        $parser->parse($buffer);
        
        return $parser->getCss();
    }
}
