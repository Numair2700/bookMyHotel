<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Modules\Availability\Contracts\AvailabilityServiceInterface;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    public function __construct(
        private readonly AvailabilityServiceInterface $availability,
    ) {}

    /**
     * FR3 — search rooms by destination, date range, price, room type and
     * sustainability. The controller stays thin: validation is in the Form
     * Request and all logic lives in the Availability module.
     */
    public function index(SearchRequest $request): Response
    {
        $criteria = $request->criteria();
        $results = $this->availability->search($criteria);

        return Inertia::render('search/results', [
            'criteria' => $criteria->toArray(),
            'results' => $results->all(),
            'count' => $results->count(),
        ]);
    }
}
