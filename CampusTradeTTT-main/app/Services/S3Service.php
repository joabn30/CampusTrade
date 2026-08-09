<?php

namespace App\Services;

use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use InvalidArgumentException;
use RuntimeException;

class S3Service
{
    private const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5 MB

    private S3Client $client;
    private string $bucket;

    public function __construct()
    {
        $this->bucket = $_ENV['AWS_S3_BUCKET'] ?? getenv('AWS_S3_BUCKET') ?: 'campustrade-joab-images';

        $this->client = new S3Client([
            'version' => 'latest',
            'region'  => $_ENV['AWS_DEFAULT_REGION'] ?? getenv('AWS_DEFAULT_REGION') ?: 'us-east-2',
        ]);
    }

    public function uploadBookImage(array $file): string
    {
        return $this->uploadImage($file, 'books');
    }

    public function uploadProfileImage(array $file): string
    {
        return $this->uploadImage($file, 'profiles');
    }

    public function deleteImage(?string $pathOrUrl): bool
    {
        $objectKey = $this->extractObjectKey($pathOrUrl);

        if ($objectKey === null) {
            return false;
        }

        try {
            $this->client->deleteObject([
                'Bucket' => $this->bucket,
                'Key'    => $objectKey,
            ]);

            return true;
        } catch (AwsException $exception) {
            throw new RuntimeException(
                'Unable to delete the image from Amazon S3.',
                0,
                $exception
            );
        }
    }

    private function uploadImage(array $file, string $folder): string
    {
        if (
            !isset(
                $file['tmp_name'],
                $file['name'],
                $file['error'],
                $file['size']
            ) ||
            $file['error'] !== UPLOAD_ERR_OK
        ) {
            throw new InvalidArgumentException(
                'The image upload was unsuccessful.'
            );
        }

        if ($file['size'] > self::MAX_FILE_SIZE) {
            throw new InvalidArgumentException(
                'The image must be 5 MB or smaller.'
            );
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            throw new InvalidArgumentException(
                'The uploaded file is invalid.'
            );
        }

        $mimeType = mime_content_type($file['tmp_name']);

        $allowedTypes = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        ];

        if ($mimeType === false || !isset($allowedTypes[$mimeType])) {
            throw new InvalidArgumentException(
                'Only JPG, PNG, and WEBP images are allowed.'
            );
        }

        $extension = $allowedTypes[$mimeType];

        $objectKey = sprintf(
            '%s/%s.%s',
            trim($folder, '/'),
            bin2hex(random_bytes(16)),
            $extension
        );

        try {
            $this->client->putObject([
                'Bucket'      => $this->bucket,
                'Key'         => $objectKey,
                'SourceFile'  => $file['tmp_name'],
                'ContentType' => $mimeType,
                'CacheControl' => 'public, max-age=31536000',
            ]);

            return $this->client->getObjectUrl($this->bucket, $objectKey);
        } catch (AwsException $exception) {
            throw new RuntimeException(
                'Unable to upload the image to Amazon S3.',
                0,
                $exception
            );
        }
    }

    private function extractObjectKey(?string $pathOrUrl): ?string
    {
        $pathOrUrl = trim((string) $pathOrUrl);

        if ($pathOrUrl === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $pathOrUrl)) {
            $parts = parse_url($pathOrUrl);
            $host = $parts['host'] ?? '';
            $path = ltrim($parts['path'] ?? '', '/');

            if (
                $host === $this->bucket . '.s3.amazonaws.com'
                || str_starts_with($host, $this->bucket . '.s3.')
                || ($host === 's3.amazonaws.com' && str_starts_with($path, $this->bucket . '/'))
                || (str_starts_with($host, 's3.') && str_starts_with($path, $this->bucket . '/'))
            ) {
                return str_starts_with($path, $this->bucket . '/')
                    ? substr($path, strlen($this->bucket) + 1)
                    : $path;
            }

            return null;
        }

        if (str_starts_with($pathOrUrl, 's3://' . $this->bucket . '/')) {
            return substr($pathOrUrl, strlen('s3://' . $this->bucket . '/'));
        }

        $pathOrUrl = ltrim(str_replace('\\', '/', $pathOrUrl), '/');

        if (str_starts_with($pathOrUrl, 'books/') || str_starts_with($pathOrUrl, 'profiles/')) {
            return $pathOrUrl;
        }

        return null;
    }
}
