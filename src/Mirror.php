<?php

namespace Mirror;

use Cz\Git\GitException;
use Cz\Git\GitRepository;

class Mirror{
    /** @var array  */
    private $mirrors = [];
    /** @var int */
    private $intervalSeconds = 60;
    /** @var int */
    private $lastRunEpoch = 0;
    /** @var GitRepository[] */
    private $repos = [];

    public static function Factory() : Mirror
    {
        return new Mirror();
    }

    public function __construct()
    {
    }

    private function nameToPath(string $name) : string
    {
        $name = preg_replace("/[^A-Za-z0-9-_]/", '_', $name);

        return "/cache/$name";
    }

    public function parse(array $config) : Mirror
    {
        $this->intervalSeconds = $config['schedule']['interval'];
        $this->mirrors = $config['mirrors'];
        foreach($this->mirrors as $mirror => $mirrorConfig){
            echo "Setting up {$mirror}...\n";
            if(file_exists($this->nameToPath($mirror))){
                $this->repos[$mirror] = new GitRepository($this->nameToPath($mirror));
            }else{
                $this->repos[$mirror] = GitRepository::cloneRepository(reset($mirrorConfig), $this->nameToPath($mirror));
            }
            foreach($mirrorConfig as $remoteName => $remotePath) {
                try {
                    $this->repos[$mirror]->removeRemote($remoteName);
                }catch(GitException $gitException){
                    // Don't care.
                }
                echo " > Adding remote {$remoteName} ($remotePath)\n";
                $this->repos[$mirror]->addRemote($remoteName, $remotePath);
            }
        }
        echo "\n";

        return $this;
    }

    public function run() : void
    {
        while(true){
            if($this->lastRunEpoch < time() - $this->intervalSeconds){
                $this->sync();
                $this->lastRunEpoch = time();
                $nextRun = date("Y-m-d H:i:s", $this->lastRunEpoch + $this->intervalSeconds);
                echo "\n\nTime now: " . date("Y-m-d H:i:s") . "... Sleeping until {$nextRun}\n\n";
            }else{
                sleep(1);
            }
        }
    }

    public function sync() : Mirror
    {
        foreach($this->mirrors as $mirror => $mirrorConfig) {
            $defaultBranch = 'master';
            echo "> Processing {$mirror}\n";
            foreach($mirrorConfig as $remoteName => $remotePath) {
                echo " > Fetching on {$remoteName}\n";
                $this->repos[$mirror]->fetch($remoteName);
                $this->repos[$mirror]->fetch($remoteName, ['--tags']);
                $this->repos[$mirror]->checkout($defaultBranch);
                try {
                    $this->repos[$mirror]->pull($remoteName, [$defaultBranch]);
                }catch(GitException $exception){
                    if(stripos($exception->getMessage(), "Couldn't find remote ref")){
                        // Do nothing.
                    }else{
                        throw $exception;
                    }
                }
            }
            foreach($mirrorConfig as $remoteName => $remotePath) {
                echo " > Pushing on {$remoteName}\n";
                #$this->repos[$mirror]->push($remoteName, [$defaultBranch]);
                $this->repos[$mirror]->push($remoteName, ['--all']);
                $this->repos[$mirror]->push($remoteName, ['--tags']);
                #$this->repos[$mirror]->push($remoteName, ['--mirror']);
            }
            echo "\n";
        }

        return $this;
    }
}