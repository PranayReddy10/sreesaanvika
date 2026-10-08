<?php

namespace App\Providers;

use App\Support\Photograph;
use App\Support\PhotographUrl;
use App\Support\Uploads;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\ImageColumn;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // One bag per request: the service remembers which, and two parts of
        // the same page asking for it must not get two different answers.
        $this->app->scoped(\App\Services\CartService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /*
         * A photograph the disk cannot find is still the shop's photograph.
         *
         * Filament asks the disk whether each stored file is there, and drops
         * from the form any it cannot confirm. On a bucket that is one request
         * per photograph per page — and when the answer is no, the saree's
         * picture quietly vanishes out of the form. It happened to this shop
         * the day its photographs moved to DigitalOcean and the files had not
         * been copied up yet: every Image box came up empty, and because the
         * box is required, Save then refused — including the new photograph
         * that had just been uploaded, which is how an upload appears to
         * disappear on save.
         *
         * Keeping the path is the honest behaviour: a file that is genuinely
         * missing shows as a broken preview, which is true and fixable,
         * instead of the record being thrown away on the shop's behalf.
         */
        /*
         * Livewire turns away anything over twelve megabytes before a line of
         * this shop's code runs, and says only that the file failed to upload.
         * A photograph off a camera is routinely larger than that, so the
         * limit becomes the server's real one — which the form also quotes, so
         * nobody is told one number and refused by another.
         */
        config(['livewire.temporary_file_upload.rules' => ['required', 'file', 'max:'.Uploads::ceiling()]]);

        /*
         * A photograph off a camera is for the person editing it.
         *
         * Eleven megabytes, six thousand pixels across — and no screen a
         * shopper owns can show more than about two thousand of them, so the
         * rest is a minute of her data spent on nothing. Every upload is
         * therefore capped and re-encoded on its way in, at a quality where
         * the difference cannot be seen: a 48-megapixel saree goes from 9.5MB
         * to 0.76MB with the weave still holding up under a pinch-zoom.
         *
         * Done to the half-finished file, before it is stored, so what lands
         * on the shop's disk is the only copy that ever existed — and done
         * quietly, because a file this cannot open is left exactly as it is
         * rather than lost.
         */
        FileUpload::configureUsing(function (FileUpload $upload): void {
            $upload->fetchFileInformation(false);

            $upload->saveUploadedFileUsing(function (FileUpload $component, TemporaryUploadedFile $file): ?string {
                Photograph::tidy((string) $file->getRealPath());

                return $component->saveUploadedFile($file);
            });

            /*
             * And when the photographs are on a bucket, the preview comes
             * from this address rather than the bucket's.
             *
             * The box fetches its picture so it can draw and crop it, and a
             * browser polices a fetch across domains where it does not police
             * an <img> tag. Hence the complaint this exists for: photographs
             * perfect on the shop, a grey "Loading" bar in the admin. A CORS
             * rule on the Space fixes it too, and this means nobody has to
             * know that.
             */
            $upload->getUploadedFileUsing(function (FileUpload $component, string $file, string | array | null $storedFileNames): ?array {
                $disk = $component->getDiskName();
                $name = ($component->isMultiple() ? ($storedFileNames[$file] ?? null) : $storedFileNames) ?? basename($file);

                return [
                    'name' => $name,
                    'size' => 0,
                    'type' => null,
                    'url' => Str::sanitizeUrl(PhotographUrl::forTheAdmin($disk, $file)),
                ];
            });
        });

        /*
         * And the same for every thumbnail in a list.
         *
         * Filament asks the disk whether each one is there before drawing it:
         * harmless on a local folder, a request per row on a bucket, and a
         * failed request there takes the whole page with it rather than one
         * picture (the existence check only catches the exception for a
         * missing *file*, not the one a bucket raises when it cannot be asked
         * at all). A broken thumbnail is a better page than a 500.
         */
        ImageColumn::configureUsing(fn (ImageColumn $column) => $column->checkFileExistence(false));
    }
}
