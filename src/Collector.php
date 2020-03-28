<?php
namespace Anton;

class Collector
{
    public function __construct(){
        $this->validator = new \Anton\Validator();
    }

    public function getLog(string $project, string $name)
    {
        $filename = $this->folders['config']. '/'.$this->folders['tmp'].'/'.$project.'/' . $name . '.log';
        if (file_exists($filename)) {
            $log = file_get_contents($filename);
            $log = trim($log);
            $log = trim($log, PHP_EOL);
    
            return $log;
        }
    
        return [];
    }

    public function getProjectConfig(string $name, string $type)
    {
        $filename = $this->folders['config']. '/'.$this->folders['tmp'].'/' .$name. '/.anton/'.$type.'.json';
        if (file_exists($filename)) {
            $file = file_get_contents($filename);
            $pipelines = (array) json_decode($file, true);
            if (count($pipelines) > 0) {
                return $pipelines;
            }
        }
        return false;
    }

    public function getConfig(string $name)
    {
        $filename = $this->folders['config']. '/projects.json';
        if (file_exists($filename)) {
            $file = file_get_contents($filename);
            $data = json_decode($file, true);

            if(!empty($data[$name])){
                return $data[$name];
            }
        }
    
        return [];
    }

    public function cloneProjectRepo(string $project,array $tmp){
        $folder = $this->folders['config'].'/'.$this->folders['tmp'].'/'.$project;
        $goDir = 'cd '.$this->folders['config'].'/'.$this->folders['tmp'];
        $cloneRepo = 'git clone '.$tmp['repo'];

        if(!file_exists($folder)) {
            exec($goDir . ' && ' . $cloneRepo. ' 2>&1');
        }
        else{
            if(!is_dir($folder)){
                echo 'Warning: Project repo shouldnt be a file.';
            }
        }
    }

    public $folders = [
        'config' => 'workspace',
        'tmp' => 'projects'
    ];

    public $filename = [
        'config' => 'config'
    ];

    public function run()
    {
        try{
            $anton = [];
            $projects = $this->getJsonFileArray($this->folders['config'].'/projects.json');
            if ($projects) {
                foreach ($projects as $key => $project) {
                    exec('mkdir -p storage/logs/'.$key);

                    $tmp = $this->getJsonFileArray($this->folders['config']. '/projects.json');
                    $this->cloneProjectRepo($key, $tmp[$key]);
                    exec('cd '. $this->folders['config']. '/projects/'.$key.' && git pull 2>&1');
                    
                    $projectConfig = $this->getJsonFileArray($this->folders['config']. '/projects/'.$key.'/.anton/config.json');

                    $config['project'] = $tmp[$key];
                    $config['pipelines'] = $projectConfig['pipelines'];
                    $config['servers'] = $projectConfig['servers'];
                    $config['steps'] = $projectConfig['steps'];
                    
                    $anton[$key] = $config;
                }
            }

            $save = $this->saveAntonConfig($anton);
            if(!$save){
                echo 'Collect Data Successfull.'.PHP_EOL;
            }
            else{
                echo 'Collect Data Failed.'.PHP_EOL;
            }
        } catch (\Exception $e){
            $this->somethingWentWrong();
            echo 'Collect Data Failed.'.PHP_EOL;
        }
    }

    public function somethingWentWrong(){
        echo 'something went wrong';
    }

    public function getJsonFileArray(string $filename){
        if (file_exists($filename)) {
            $file = file_get_contents($filename);
            return json_decode($file, true);
        }

        return false;
    }

    public function loadProjectConfig(string $project){
        $filename = 'workspace/projects/'.$project.'/.anton/config.json';
        if (file_exists($filename)) {
            $file = file_get_contents($filename);
            $config = (array) json_decode($file, true);
            return $config;
        }
        return false;
    }

    public function saveAntonConfig(array $anton){
        $this->validator->validate($anton);
        if(!$this->validator->hasErrors()){
            file_put_contents('storage/anton.json', json_encode($anton, JSON_UNESCAPED_SLASHES));
            return false;
        }

        return true;
    }
}
