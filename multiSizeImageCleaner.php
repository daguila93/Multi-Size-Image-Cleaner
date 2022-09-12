<?php
main(true);

/**
 * @param $isContinue
 * @return void
 */
function main($isContinue)
{
    do {
        //Get the file`s path from user`s input
        $usersInput = getFilePathFromUsersInput();
        //Transform user`s input string to a real path
        $originalFilePath = realpath($usersInput);

        //Tests if the file exists
        if (file_exists($originalFilePath)) {

            //Algorithm
            //1) Get all the File Information
            $path_parts = pathinfo($originalFilePath);
            //2) Make a copy of the file
            $copiedFile = createFileCopy($path_parts);
            //3) Unzip the Copied File
            $unzippedFile = unzip($copiedFile);
            // 4) Get all image names from the unzipped file and put into .txt file
            $filesAndFolders = getAllFilesAndFolders($unzippedFile);
            // 5) Clean all images without erasing the Original ones
            cleanAllNotOriginalImages($filesAndFolders);
            //6) Zip the copied file
            zip($unzippedFile);
            //7) Delete the file copy
            deleteFileCopy($copiedFile);
            //8) Delete the decompressed file
            deleteDecompressedFile($unzippedFile);

            $isContinue = false;
        } else {
            echo "$usersInput not found!\n";
        }

    } while ($isContinue);
}

/**
 * @param array $path_parts
 * @return array|string|string[]
 */
function createFileCopy(array $path_parts)
{
    $fullPath = $path_parts['dirname'] . DIRECTORY_SEPARATOR . $path_parts['basename'];
    $copiedFile = getNewFileName($path_parts);
    shell_exec("cp $fullPath ./$copiedFile");
    return pathinfo($path_parts['dirname'] . DIRECTORY_SEPARATOR . $copiedFile);
}

/**
 * @param $fileName
 * @return string
 */
function getNewFileName($fileName): string
{
    return basename($fileName['filename'], ".tar") . '_copy.tar.gz';
}

/**
 * @param $zippedFile
 * @return string
 */
function unzip($zippedFile): string
{
    $decompressedFile = basename($zippedFile["filename"], ".tar");
    $zippedFullFileName = $zippedFile['basename'];
    shell_exec('mkdir ' . $decompressedFile . ' && tar -zxvf ' . $zippedFullFileName . ' -C ' . $decompressedFile);
    return $decompressedFile;
}

/**
 * @param $zippedFileCopy
 * @return void
 */
function deleteFileCopy($zippedFileCopy)
{
    unlink($zippedFileCopy["basename"]);
}

/**
 * @param string $unzippedFile
 * @return void
 */
function deleteDecompressedFile(string $unzippedFile)
{
    system("rm -rf " . escapeshellarg($unzippedFile));
}

/**
 * @param $fileToZip
 * @return string
 */
function zip($fileToZip): string
{
    $newName = $fileToZip . '_images_cleaned.tar.gz';
    shell_exec('tar -czf ' . $newName . " " . $fileToZip);
    return $newName;
}

/**
 * @param $filesAndFolders
 * @return void
 */
function cleanAllNotOriginalImages($filesAndFolders)
{
    $regex = '/\d+x\d+\.\w{3,}$/m';

    foreach ($filesAndFolders as $ff) {
        if (is_file($ff)) {
            if (preg_match($regex, $ff)) {
                unlink($ff);
            }
        }
    }
}

/**
 * @param $dir
 * @param array $results
 * @return array
 */
function getAllFilesAndFolders($dir, array &$results = array()): array
{
    $files = scandir($dir);

    foreach ($files as $key => $value) {
        $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
        if (!is_dir($path)) {
            $results[] = $path;
        } else if ($value != "." && $value != "..") {
            getAllFilesAndFolders($path, $results);
            $results[] = $path;
        }
    }
    return $results;
}

/**
 * @return string
 */
function getFilePathFromUsersInput(): string
{
    return trim(readline('Type the file`s Path: '));
}
