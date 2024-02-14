<?php

namespace Tests\Services;

use App\Models\Item;
use App\Models\Review;
use App\Services\Models\ItemService;
use App\Services\Models\ReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\Factories;
use Tests\Traits\Fixtures;
use Tests\Traits\Mocks;

class ReviewServiceTest extends TestCase
{

    use RefreshDatabase;
    use Factories;
    use Fixtures;
    use Mocks;

    private $review;
    private $reviewService;
    private $user;

    public function setUp(): void
    {
      parent::setUp();

      $this->user = $this->createUser();

      $this->review = app(Review::class);
      $this->reviewService = app(ReviewService::class);

      $this->createStorageDownloadsMock();
      $this->createImdbRatingMock();
    }

    /** @test */
    public function review_should_be_created_on_item_creation(): void
    {
      $this->createGuzzleMock(
        $this->tmdbFixtures('movie/details'),
        $this->tmdbFixtures('movie/alternative_titles')
      );

      $item = app(Item::class);
      $itemService = app(ItemService::class);
      $itemService->create($this->floxFixtures('movie'), $this->user->id);
      $item = $item->all()->first();

      $this->assertIsObject($item->review);
      $this->assertCount(1, $item->review);
      $this->assertEquals(0, $item->review[0]->rating);
    }

    /** @test */
    public function it_should_change_rating()
    {
      $review = $this->createReview();
      $reviewId = $review->id;

      $itemBefore = $this->review->find($reviewId);
      $this->reviewService->changeRating($reviewId, 3);
      $itemAfter = $this->review->find($reviewId);

      $this->assertEquals(1, $itemBefore->rating);
      $this->assertEquals(3, $itemAfter->rating);
      $this->assertEquals($itemBefore->last_seen_at, $itemAfter->last_seen_at);
    }
}
