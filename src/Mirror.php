<?php

namespace Mirror;

use Cz\Git\GitException;
use Cz\Git\GitRepository;

class Mirror{
    /** @var array  */
    private $mirrors = [];
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

    public function parse(array $mirrors) : Mirror
    {
        $this->mirrors = $mirrors['mirrors'];
        foreach($this->mirrors as $mirror => $mirrorConfig){
            echo "Setting up {$mirror}...\n";
            if(file_exists($this->nameToPath($mirror))){
                $this->repos[$mirror] = new GitRepository($this->nameToPath($mirror));
            }else{
                $this->repos[$mirror] = GitRepository::mirror($this->nameToPath($mirror), reset($mirrorConfig));
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

    public function run() : Mirror
    {
        foreach($this->mirrors as $mirror => $mirrorConfig) {
            #$defaultBranch = 'master';
            echo "> Processing {$mirror}\n";
            foreach($mirrorConfig as $remoteName => $remotePath) {
                echo " > Fetching on {$remoteName}\n";
                $this->repos[$mirror]->fetch($remoteName);
                $this->repos[$mirror]->fetch($remoteName, ['--tags']);
                #echo "  > Checking out {$defaultBranch}\n";
                #try {
                #    $this->repos[$mirror]->checkout("{$defaultBranch}");
                #    $this->repos[$mirror]->pull($remoteName,["{$defaultBranch}", "--allow-unrelated-histories"]);
                #}catch(GitException $gitException){
                #    echo "   > Doesn't exist yet.\n";
                #}
                #exec("git branch -r | grep -v '\->' | while read remote; do git branch --track \"\${remote#origin/}\" \"\$remote\"; done");
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