<?php

namespace App\Providers;

use App\Support\PhotographUrl;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\ImageColumn;
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
        FileUpload::configureUsing(function (FileUpload $upload): void {
            $upload->fetchFileInformation(false);

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
