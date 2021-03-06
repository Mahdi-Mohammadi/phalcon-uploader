<?php
namespace Uploader;

use Uploader\Helpers\Format;
use Phalcon\Http\Request;

/**
 * Uploader executable class
 *
 * @package   Uploader
 * @since     PHP >=5.4
 * @version   1.0
 * @author    Stanislav WEB | Lugansk <stanisov@gmail.com>
 * @copyright Stanislav WEB
 */
class Uploader
{

    /**
     * Request
     *
     * @var \Phalcon\Http\Request $rules
     */
    private $request;

    /**
     * File
     *
     * @var \Phalcon\Http\Request\File $files
     */
    private $files;

    /**
     * Validation Rules
     *
     * @var array $rules
     */
    private $rules = [];

    /**
     * Uploaded files array
     *
     * @var array $info
     */
    private $info;

    /**
     * Validator
     *
     * @var \Uploader\Validator
     */
    private $validator;

    /**
     * Initialize rules
     *
     * @param array $rules
     */
    public function __construct($rules = [])
    {
        if (empty($rules) === FALSE) {
            $this->setRules($rules);
        }

        // get validator
        $this->validator = new Validator();
        // get current request
        $this->request = new Request();
    }

    /**
     * Setting up rules for uploaded files
     *
     * @param array $rules
     * @return Uploader
     */
    public function setRules(array $rules)
    {
        foreach ($rules as $key => $values) {

            if ((is_array($values) === TRUE && empty($values) === FALSE) || is_callable($values)) {
                $this->rules[$key] = $values;
            } else {
                $this->rules[$key] = trim($values);
            }
        }

        return $this;
    }

    /**
     * Check if upload files are valid
     *
     * @return bool
     */
    public function isValid()
    {
        // get files for upload
        if (!$this->files) {
            $this->files = $this->request->getUploadedFiles();
        }
        if (sizeof($this->files) > 0) {

            // do any actions if files exists

            foreach ($this->files as $n => $file) {
                if (!$file->isUploadedFile()) {
                    continue;
                }
                // apply all the validation rules for each file

                foreach ($this->rules as $key => $rule) {

                    if (method_exists($this->validator, 'check' . ucfirst($key)) === TRUE) {
                        $this->validator->{'check' . ucfirst($key)}($file, $rule);
                    }
                }
            }
        }

        $errors = $this->getErrors();

        return (empty($errors) === TRUE) ? TRUE : FALSE;
    }

    /**
     * Check if upload files are valid
     *
     * @return void
     */
    public function move()
    {
        // do any actions if files exists

        foreach ($this->files as $n => $file) {
            if (!$file->isUploadedFile()) {
                continue;
            }
            $filename = $file->getName();

            if (isset($this->rules['hash']) === TRUE) {
                if (empty($this->rules['hash']) === TRUE) {
                    $this->rules['hash'] = 'md5';
                }
                if (!is_string($this->rules['hash']) === TRUE) {
                    $filename = call_user_func($this->rules['hash']) . '.' . $file->getExtension();
                } else {
                    $filename = $this->rules['hash'] . '.' . $file->getExtension();
                }
            }

            if (isset($this->rules['sanitize']) === TRUE) {
                $filename = Format::toLatin($filename, '', TRUE);
            }

            if (isset($this->rules['directory'])) {
                $tmp = rtrim($this->rules['directory'], '/') . DIRECTORY_SEPARATOR . $filename;
            } else {
                $tmp = rtrim($this->rules['dynamic'], '/') . DIRECTORY_SEPARATOR . $filename;

            }

            // move file to target directory
            $isUploaded = $file->moveTo($tmp);

            if ($isUploaded === TRUE) {
                $this->info[] = [
                    'path'      => $tmp,
                    'directory' => dirname($tmp),
                    'filename'  => $filename,
                    'size'      => $file->getSize(),
                    'extension' => $file->getExtension(),
                ];
            }
        }

        return $this->getInfo();
    }

    /**
     * Return errors messages
     *
     * @return array
     */
    public function getErrors()
    {
        // error container
        return $this->validator->errors;
    }

    /**
     * Get uploaded files info
     *
     * @return \Phalcon\Session\Adapter\Files
     */
    public function getInfo()
    {
        // error container
        return $this->info;
    }

    /**
     * Truncate uploaded files
     */
    public function truncate()
    {
        if (empty($this->info) === FALSE) {
            foreach ($this->info as $n => $file) {
                if (file_exists($file['path'])) {
                    unlink($file['path']);
                }
            }
        }
    }

    /**
     * Created By Mahdi Mohammadi
     *
     * Set Custom File(s)
     *
     * @param array $files
     * @return bool
     */
    public function setFiles($files = array())
    {
        if (is_array($files)) {
            $this->files = $files;
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Return Unique Name
     *
     * @param string $prefix
     * @return string
     */
    public function getUniqueName($prefix = '')
    {
        return uniqid($prefix);
    }
}
