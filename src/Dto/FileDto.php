<?php
namespace App\Dto;

use App\Entity\CommentFile;
use Symfony\Component\Validator\Constraints as Assert;

class FileDto extends CommentFile
{
    /**
     * @Assert\File(
     *      maxSize = "10M"
     *      )
     */
    public $file;
}
