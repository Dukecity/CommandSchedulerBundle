<?php /** @noinspection ALL */

declare(strict_types=1);

use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Component\ErrorHandler\ErrorHandler;

$file = __DIR__.'/../vendor/autoload.php';
if (!file_exists($file)) {
    throw new RuntimeException('Install dependencies to run test suite.');
}
/** @noinspection PhpIncludeInspection */
$autoload = require $file;

# https://github.com/symfony/symfony/issues/53812#issuecomment-1962740145
set_exception_handler([new ErrorHandler(), 'handleException']);

if (is_dir(__DIR__.'/../build')) {
    #echo "Removing files in the build directory.\n".__DIR__."\n";
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(__DIR__.'/../build/', RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($files as $fileinfo) {
        $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
        @$todo($fileinfo->getRealPath());
    }
} else {
    // Creating the build dir, to output some potential datas, and the code coverage if wanted
    mkdir(__DIR__.'/../build');
}

// Legacy for doctrine/annotation v1
// Registers automatically all doctrine annotations when required
if(method_exists(AnnotationRegistry::class, 'registerLoader'))
{
    AnnotationRegistry::registerLoader(function ($class) use ($autoload) {
        $autoload->loadClass($class);

        return class_exists($class, false);
    });
}