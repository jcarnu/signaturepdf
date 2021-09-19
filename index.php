<?php

$f3 = require(__DIR__.'/vendor/fatfree/lib/base.php');
$f3->set('DEBUG',1);
$f3->set('UPLOADS', $f3->get('ROOT').'/data/');

if(!is_dir($f3->get('UPLOADS'))) {
    mkdir($f3->get('UPLOADS'));
}
$f3->route('GET /',
    function($f3) {
        $f3->set('key', $f3->get('PARAMS.key'));
        echo View::instance()->render('index.html.php');
    }
);
$f3->route('POST /upload',
    function($f3) {
        $files = Web::instance()->receive(function($file,$formFieldName){
                if(Web::instance()->mime($file['tmp_name'], true) != 'application/pdf') {
                    
                    return false; 
                }
                if($file['size'] > (20 * 1024 * 1024)) { // if bigger than 20 MB
                    
                    return false; 
                }
                return true;
        }, true);
        
        $key = null;
        foreach($files as $file => $valid) {
            if(!$valid) {
                continue;
            }
            $key = md5_file($file);
            rename($file, $f3->get('UPLOADS').'/'.$key.'.pdf');
        }
        
        if(!$key) {
            $f3->error(403);
        }

        return $f3->reroute('/'.$key);
    }
);
$f3->route('GET /@key',
    function($f3) {
        $f3->set('key', $f3->get('PARAMS.key'));
        echo View::instance()->render('pdf.html.php');
    }
);
$f3->route('POST /@key/save',
    function($f3) {
        $key = $f3->get('PARAMS.key');
        $svgData = $_POST['svg'];

        $svgFiles = "";
        foreach($svgData as $index => $svgItem) {
            $svgFile = $f3->get('UPLOADS').'/'.$key.'_'.$index.'.svg';
            file_put_contents($svgFile, $svgItem);
            $svgFiles .= $svgFile . " ";
        }


        shell_exec(sprintf("rsvg-convert -f pdf -o %s %s", $f3->get('UPLOADS').'/'.$key.'.svg.pdf', $svgFiles));
        shell_exec(sprintf("pdftk %s multibackground %s output %s", $f3->get('UPLOADS').'/'.$key.'.svg.pdf', $f3->get('UPLOADS').'/'.$key.'.pdf', $f3->get('UPLOADS').'/'.$key.'_signe.pdf'));
        
        Web::instance()->send($f3->get('UPLOADS').'/'.$key.'_signe.pdf');
    }
);
$f3->run();