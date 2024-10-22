<?php

namespace Hestia\WebApp\Installers\Pocketbase\PocketbaseUtils;

use Hestia\System\HestiaApp;

class PocketbaseUtil
{
    protected $appcontext;

    public function __construct(HestiaApp $appcontext)
    {
        $this->appcontext = $appcontext;
    }

    public function createDir(string $dir)
    {
        $result = null;
        $this->appcontext->runUser(
            "v-run-cli-cmd",
            ["mkdir", "-p", $dir],
            $result
        );
        return $result->code === 0;
    }

    public function moveFile(string $fileA, string $fileB)
    {
        $result = null;

        if (
            !$this->appcontext->runUser(
                "v-move-fs-file",
                [$fileA, $fileB],
                $result
            )
        ) {
            throw new \Exception(
                "Error updating file in: " . $fileA . " " . $result->text
            );
        }

        return $result;
    }

    public function parseTemplate($template, $search, $replace): array
    {
        $data = [];

        $file = fopen($template, "r");
        while ($l = fgets($file)) {
            $data[] = str_replace($search, $replace, $l);
        }
        fclose($file);

        return $data;
    }

    public function downloadFile(string $url, string $destination)
    {
        $cmd = sprintf(
            "curl -L -o %s %s",
            escapeshellarg($destination),
            escapeshellarg($url)
        );

        $output = [];
        $return_var = 0;
        exec($cmd, $output, $return_var);

        if ($return_var !== 0) {
            $this->appcontext->runUser("v-log-action", [
                "Error",
                "Web",
                "Failed to download file: " . implode("\n", $output),
            ]);
            return false;
        }

        return true;
    }

    public function unzipFile(string $zipFile, string $destination)
    {
        $result = null;
        $this->appcontext->runUser(
            "v-run-cli-cmd",
            ["unzip", "-o", $zipFile, "-d", $destination],
            $result
        );
        return $result->code === 0;
    }

    public function deleteFile(string $file)
    {
        $result = null;
        $this->appcontext->runUser("v-delete-fs-file", [$file], $result);
        return $result->code === 0;
    }

    public function makeExecutable(string $file)
    {
        $result = null;
        $this->appcontext->runUser(
            "v-change-fs-file-permission",
            [$file, "0755"],
            $result
        );
        return $result->code === 0;
    }

    public function reloadSystemd()
    {
        exec("sudo systemctl daemon-reload", $output, $returnVar);
        return $returnVar === 0;
    }

    public function startService(string $serviceName)
    {
        exec("sudo systemctl start $serviceName", $output, $returnVar);
        return $returnVar === 0;
    }

    public function enableService(string $serviceName)
    {
        exec("sudo systemctl enable $serviceName", $output, $returnVar);
        return $returnVar === 0;
    }
}
