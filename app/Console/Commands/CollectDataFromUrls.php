<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Url;
use HeadlessChromium\Browser\ProcessAwareBrowser;
use HeadlessChromium\BrowserFactory;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'collect-data-from-urls', description: 'Collect the data from the urls.')]
class CollectDataFromUrls extends Command implements Isolatable
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'collect-data-from-urls';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Collect the data from the urls';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $browser = (new BrowserFactory)->createBrowser();

        Url::query()
            ->where('is_processed', false)
            ->chunk(100, function ($urls) use ($browser) {
                foreach ($urls as $url) {
                    $data = $this->getDate($browser, $url->url);

                    if (count($data) != 0) {
                        Product::create($data);

                        $url->update(['is_processed' => true]);

                        $this->info("Collected data from: {$url->url} successfully...");
                    }
                }
            });

        $browser->close();

        $this->info('Done....');
    }

    protected function getDate(ProcessAwareBrowser $browser, string $url): array
    {
        try {
            $page = $browser->createPage();
            $page->navigate($url)->waitForNavigation();

            $data = $page->callFunction($this->scriptJs())->getReturnValue();

            return $data;
        } catch (\Throwable $th) {
            $this->error($url);

            return [];
        }
    }

    protected function scriptJs(): string
    {
        return 'async function(){
            function getFeatures() {
                const features = document.querySelectorAll(".features .feature");

                const featuresData = [];

                features.forEach((feature) => {
                    const launcher = feature.querySelector(".launcher");
                    const allData = feature.querySelectorAll(".all_data p");

                    const dataItems = [];
                    allData.forEach((item) => {
                        dataItems.push(item.textContent.trim());
                    });

                    featuresData.push({
                        launcher: launcher.textContent.trim(),
                        all_data: dataItems,
                    });
                });

                return featuresData;
            }

            function getImages() {
                let images = document.querySelectorAll(".imagen_esquema img");

                let urls = [];
                images.forEach((image) => {
                    urls.push(image.src);
                });

                return urls;
            }

            return {
                name: document.querySelector(".product_data h1").textContent.trim(),
                code: document.querySelector(".product_data h2").textContent.trim(),
                details: document.querySelector(".details").textContent.trim(),
                features: getFeatures(),
                main_image: document.querySelector(".image_wrapper img").src,
                images: getImages(),
            }
        }';
    }
}
