<?php

namespace App\Http\Controllers;

use App\Events\UserWasInvited;
use App\Http\Requests\EventRequest;
use App\Http\Requests\InvitationRequest;
use App\Repositories\Eloquent\Criteria\AutoloadFilter;
use App\Repositories\Interfaces\EventRepository;
use App\Repositories\Interfaces\InvitationRepository;
use App\Repositories\Interfaces\UserRepository;
use App\Services\Events\EventAttacherService;
use App\Services\Events\EventCreatorService;
use App\Services\Events\EventUpdaterService;
use App\Services\Events\UserEventsFetcherService;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EventsController extends Controller
{
	private $evant_attacher, 
            $user_events_fetcher, 
            $event_creator,
            $user_repository,
            $event_repository;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        EventAttacherService $evant_attacher,
        UserEventsFetcherService $user_events_fetcher, 
        EventCreatorService $event_creator,
        EventUpdaterService $event_updater,
        UserRepository $user_repository,
        EventRepository $event_repository)
    {
        $this->event_repository = $event_repository;
        $this->user_repository = $user_repository;
        $this->user_events_fetcher = $user_events_fetcher;
        $this->evant_attacher = $evant_attacher;
        $this->event_creator = $event_creator;
        $this->event_updater = $event_updater;
    }

    public function index()
    {
        // Get owned events.
        $owned_events = $this->user_events_fetcher->ownEvents();

        // Get events I'm joinde.
        $joined_events = $this->user_events_fetcher->joinedEvents();

        // Get events I'm invited to.
        $unconfirmed_events = $this->user_events_fetcher->withInvitationsEvents();

        return view('events.index', compact('owned_events',
            'joined_events', 'unconfirmed_events'));
    }

    public function edit($id)
    {
        $event = $this->event_repository->with('place')->find($id);
        
        $this->authorize('edit-event', $event);
    
        return view('events.edit', compact('event'));
    }

    public function store(EventRequest $request)
    {
        try {
            $this->event_creator->make($request);
            \Alert::success('New event was created!');
        } catch (Exception $e) {
            \Log::error($e->getMessage());
            \Alert::error('Whops! Something went wrong!');
        }

        return redirect()->route('events.index');
    }

    public function create()
    {
        return view('events.create');
    }

    public function update(EventRequest $request, $id)
    {
        try {
            $event = $this->event_repository->find($id);
            $this->authorize('edit-event', $event);
            $this->event_updater->make($id, $request);

            \Alert::success('Event was updated!');
        } catch (Exception $e) {
            \Log::error($e->getMessage());
            \Alert::error('Whops! Something went wrong!');
        }

        return redirect()->route('events.index');
    }

    public function destroy($id)
    {
        try {
            $event = $this->event_repository->find($id);
            $this->authorize('remove-event', $event);
            $this->event_repository->delete($id);

            \Alert::success('Event was deleted!');
        } catch (Exception $e) {
            \Log::error($e->getMessage());
            \Alert::error('Whops! Something went wrong!');
        }

        return redirect()->route('events.index');
    }

    public function show($id, $slug)
    {	
    	$event = $this->event_repository->with(['place', 'owner', 'invitations.user'])->find($id);

        $this->authorize('show-event', $event);

    	return view('events.show', compact('event'));
    }

    public function join($id)
    {
        try {
            $user = \Auth::user();
            $event = $this->event_repository->find($id);

            $this->authorize('join-event', $event);

            $this->evant_attacher->join($user, $event);

            \Alert::success('You join to event: ' . $event->name);

        } catch(\Exception $e) {
            \Log::error($e->getMessage());
            \Alert::danger('Opps! Something went wrong!');
        }
        
        return redirect()->back();
    }

    public function leave($id)
    {
        try {
            $user = \Auth::user();
            $event = $this->event_repository->find($id);

            if(! ($user->can('leave-event', $event) || $user->can('join-event', $event))) {
                throw new AccessDeniedHttpException;
            }

            $this->evant_attacher->leave($user, $event);

            \Alert::success('You leaved event: ' . $event->name);

        } catch(\Exception $e) {
            \Log::error($e->getMessage());
            \Alert::danger('Opps! Something went wrong!');
        }
        
        return redirect()->back();
    }

    public function invite(InvitationRequest $request)
    {
        $id = $request->get('event_id');
        $emails = explode(',', $request->get('emails'));
        $message = (string) $request->get('message');

        try {
            $users = $this->user_repository->findWhereIn('email', (array) $emails);
            $event = $this->event_repository->find($id);

            $this->authorize('invite-to-event', $event);

            foreach ($users as $key => $user) {
                $this->evant_attacher->invite($user, $event);
                event(new UserWasInvited($user, $event, $message));
            }
            
            \Alert::success('You invite ' . count($emails) . ' user(s) to event: ' . $event->name);

        } catch(\Exception $e) {
            \Log::error($e->getMessage());
            \Alert::danger('Opps! Something went wrong!');
        }
        
        return redirect()->back();
    }

    public function usersAutoload()
    {
        $query = request()->get('q');

        $this->user_repository->addCriteria(new AutoloadFilter($query));

        return $this->user_repository->take(20,['email', 'nick'])
            ->map(function($user) {
                return "{$user->nick}<{$user->email}>";
            });
    }
}
