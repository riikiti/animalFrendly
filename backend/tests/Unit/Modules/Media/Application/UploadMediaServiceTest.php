<?php

declare(strict_types=1);

use App\Modules\Media\Application\Services\UploadMediaService;
use App\Modules\Media\Domain\Entities\Media;
use App\Modules\Media\Domain\Exceptions\InvalidMediaUploadException;
use App\Modules\Media\Domain\Repositories\MediaRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('stores an uploaded image and creates a media record', function (): void {
    Storage::fake('local');

    $ownerId = Id::generate();
    $file = UploadedFile::fake()->image('cat.jpg', 100, 100)->size(50);

    $media = Mockery::mock(MediaRepositoryInterface::class);
    $media->shouldReceive('nextIdentity')->once()->andReturn(Id::generate());
    $media->shouldReceive('save')->once()->with(Mockery::on(fn (Media $m) => $m->ownerUserId()->equals($ownerId)));

    $service = new UploadMediaService($media);
    $result = $service->upload($file, $ownerId);

    expect($result->mimeType())->toBe('image/jpeg')
        ->and($result->ownerUserId()->equals($ownerId))->toBeTrue();

    Storage::disk('local')->assertExists($result->path());
});

it('rejects unsupported mime types', function (): void {
    Storage::fake('local');

    $file = UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf');

    $media = Mockery::mock(MediaRepositoryInterface::class);
    $media->shouldNotReceive('save');

    $service = new UploadMediaService($media);
    $service->upload($file, Id::generate());
})->throws(InvalidMediaUploadException::class);

it('rejects files larger than the configured limit', function (): void {
    Storage::fake('local');
    config(['media.max_size_kb' => 100]);

    $file = UploadedFile::fake()->image('cat.jpg')->size(200);

    $media = Mockery::mock(MediaRepositoryInterface::class);
    $media->shouldNotReceive('save');

    $service = new UploadMediaService($media);
    $service->upload($file, Id::generate());
})->throws(InvalidMediaUploadException::class);
