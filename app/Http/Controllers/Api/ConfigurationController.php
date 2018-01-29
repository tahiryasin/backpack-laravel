<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Api\ConfigurationFavoriteRequest;
use App\Http\Requests\Api\ConfigurationRequest;
use App\Models\Configuration\Configuration;
use App\Models\Configuration\DoorConfiguration;
use App\Models\Configuration\DoorMailboxConfiguration;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConfigurationController extends ApiController
{
    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function index(Request $request)
    {
        $favorite = $request->input('favorite');

        $query = Auth::user()
            ->configurations()
            ->where('active', true);

        if ($favorite) {
            $query->where('is_favorite', true);
        }

        return $query->paginate();
    }

    /**
     * @param ConfigurationRequest $request
     * @return Configuration
     * @throws \Exception
     * @throws \Throwable
     */
    public function store(ConfigurationRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $attributes = $request->only([
                'type_id',
                'image',
                'screenshot',
                'width',
                'height',
                'scale',
            ]);

            /** @var Configuration $configuration */
            $configuration = Auth::user()->configurations()->make();
            $configuration->fill($attributes);
            if (!$configuration->save()) {
                abort(500);
            }

            $doors = $request->input('doors');
            foreach ($doors as $door) {
                $attributes = array_only($door, [
                    'door_id',
                    'door_color_id',
                    'door_size_id',
                    'door_glass_id',
                    'door_handle_id',
                    'door_hinge_id',
                    'door_lock_id',
                    'door_weldorpel_id',
                    'custom',
                    'width',
                    'height',
                    'bounds',
                    'handle_right',
                    'three_point_lock',
                    'draft_trap',
                ]);

                /** @var DoorConfiguration $doorConfiguration */
                $doorConfiguration = $configuration->doors()->make();
                $doorConfiguration->fill($attributes);
                if (!$doorConfiguration->save()) {
                    abort(500);
                }

                if ($mailbox = array_get($door, 'door_mailbox')) {
                    $attributes = array_only($mailbox, [
                        'door_mailbox_id',
                        'bounds',
                    ]);

                    /** @var DoorMailboxConfiguration $doorMailbox */
                    $doorMailbox = $doorConfiguration->doorMailbox()->make();
                    $doorMailbox->fill($attributes);
                    if (!$doorMailbox->save()) {
                        abort(500);
                    }
                }
            }

            return $configuration->fresh([
                'doors.door',
                'doors.door.colors' => function (HasMany $relation) {
                    $relation->where('active', true);
                },
                'doors.doorColor.sizes',
                'doors.doorSize',
                'doors.doorGlass',
                'doors.doorHandle',
                'doors.doorHinge',
                'doors.doorLock',
                'doors.doorMailbox.doorMailbox',
                'doors.doorWeldorpel',
            ]);
        });
    }

    /**
     * @param Configuration $configuration
     * @return Configuration
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(Configuration $configuration)
    {
        $this->authorize($configuration);

        return $configuration->load([
            'doors.door',
            'doors.door.colors' => function (HasMany $relation) {
                $relation->where('active', true);
            },
            'doors.doorColor.sizes',
            'doors.doorSize',
            'doors.doorGlass',
            'doors.doorHandle',
            'doors.doorHinge',
            'doors.doorLock',
            'doors.doorMailbox.doorMailbox',
            'doors.doorWeldorpel',
        ]);
    }

    /**
     * @param ConfigurationRequest $request
     * @param Configuration $configuration
     * @return Configuration
     * @throws \Exception
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function update(ConfigurationRequest $request, Configuration $configuration)
    {
        $this->authorize($configuration);

        return DB::transaction(function () use ($configuration, $request) {
            $attributes = $request->only([
                'screenshot',
            ]);

            if (!($configuration->update($attributes))) {
                abort(500);
            }

            $doors = $request->input('doors');
            $doorIds = [];
            foreach ($doors as $door) {
                $attributes = array_only($door, [
                    'door_id',
                    'door_color_id',
                    'door_size_id',
                    'door_glass_id',
                    'door_handle_id',
                    'door_hinge_id',
                    'door_lock_id',
                    'door_weldorpel_id',
                    'custom',
                    'width',
                    'height',
                    'bounds',
                    'handle_right',
                    'three_point_lock',
                    'draft_trap',
                ]);

                /** @var DoorConfiguration $doorConfiguration */
                if (
                !(
                    ($doorConfigurationId = array_get($door, 'id')) &&
                    ($doorConfiguration = $configuration->doors()->find($doorConfigurationId))
                )
                ) {
                    $doorConfiguration = $configuration->doors()->make();
                }

                $doorConfiguration->fill($attributes);
                if (!$doorConfiguration->save()) {
                    abort(500);
                }

                if ($mailbox = array_get($door, 'door_mailbox')) {
                    $attributes = array_only($mailbox, [
                        'door_mailbox_id',
                        'bounds',
                    ]);

                    /** @var DoorMailboxConfiguration $doorMailbox */
                    $doorMailbox = $doorConfiguration->doorMailbox ?: $doorConfiguration->doorMailbox()->make();
                    $doorMailbox->fill($attributes);
                    if (!$doorMailbox->save()) {
                        abort(500);
                    }
                } else if ($doorConfiguration->doorMailbox) {
                    if (!$doorConfiguration->doorMailbox->delete()) {
                        abort(500);
                    }
                }

                $doorIds[] = $doorConfiguration->id;
            }

            $doors = $configuration->doors();

            if (count($doorIds)) {
                $doors->whereNotIn('id', $doorIds);
            }

            $doors->get()
                ->each(function (DoorConfiguration $door) {
                    $door->delete();
                });

            return $configuration->fresh([
                'doors.door',
                'doors.door.colors' => function (HasMany $relation) {
                    $relation->where('active', true);
                },
                'doors.doorColor.sizes',
                'doors.doorSize',
                'doors.doorGlass',
                'doors.doorHandle',
                'doors.doorHinge',
                'doors.doorLock',
                'doors.doorMailbox.doorMailbox',
                'doors.doorWeldorpel',
            ]);
        });
    }

    /**
     * @param Configuration $configuration
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Exception
     * @throws \Throwable
     */
    public function destroy(Configuration $configuration)
    {
        $this->authorize($configuration);

        if ($configuration->orders->count() || $configuration->quotes->count()) {
            if (!$configuration->update(['active' => false])) {
                abort(500);
            }
        } else {
            \DB::transaction(function () use ($configuration) {
                if (!$configuration->delete()) {
                    abort(500);
                }
            });
        }

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * @param ConfigurationFavoriteRequest $request
     * @param Configuration $configuration
     * @return Configuration
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function favorite(ConfigurationFavoriteRequest $request, Configuration $configuration)
    {
        $this->authorize('update', $configuration);

        if (!$configuration->update([
            'title' => $request->input('title') ?: $configuration->title,
            'is_favorite' => true
        ])) {
            abort(500);
        }

        return $configuration;
    }

    /**
     * @param Configuration $configuration
     * @return Configuration
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function unfavorite(Configuration $configuration)
    {
        $this->authorize('update', $configuration);

        if (!$configuration->update(['is_favorite' => false])) {
            abort(500);
        }

        return $configuration;
    }
}
