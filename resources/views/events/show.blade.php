@extends('layouts.app')

@section('content')
	<section class="section">
		<div class="container">
			@include('alert::bootstrap')
			<div class="card">
				<div class="card-body">
					<header class="header">
						<div class="row">
							<div class="col-md-8">
								<h2>
									{{ $event->name }}<br>
									<small class="text-primary">{{ $event->full_date }}</small>
								</h2>
							</div>
							<div class="col-md-4 text-md-right">
								@if($event->is_public)
									<h3>
										<i class="material-icons">public</i> {{ __('Public event') }}
									</h3>
								@else
									<h3>
										<i class="material-icons">lock_outline</i> {{ __('Private event') }}
									</h3>
								@endif
							</div>
						</div>
					</header>
					<dl class="row">
					  	<dt class="col-sm-2">{{ __('Organized by') }}</dt>
					  	<dd class="col-sm-10">
					  		{{ $event->owner->nick }} 
					  		( <a href="mailto:{{ $event->owner->email }}">{{ $event->owner->email }}</a> )
					  	</dd>
					  	<dt class="col-sm-2">{{ __('Description') }}</dt>
						<dd class="col-sm-10">
						    <p>
								{{ $event->description or '- '.__('No description').' -' }}
							</p>
						</dd>
						<dt class="col-sm-2">{{ __('Participants') }}</dt>
						<dd class="col-sm-10">
						    @forelse($event->invitations as $invitation)
								<div class="row">
									<div class="pl-3 pr-2">
										<i class="material-icons" 
										title="{{ $invitation->is_confirmed ? __('Will come') : __('Did not confirm yet') }}">
											{{ $invitation->is_confirmed ? 'check_box' : 'check_box_outline_blank' }}
										</i>
									</div>
									<div class="">
										{{ $invitation->user->nick }} 
										( <a href="mailto:{{ $invitation->user->email }}">{{ $invitation->user->email }}</a> )
									</div>
									
								</div>
						    @empty
								- {{ __('Nobody join this event yet') }} -
						    @endforelse
						</dd>
						<dt class="col-sm-2">{{ __('Place') }}</dt>
						<dd class="col-sm-10">
							{{ $event->place->name }}<br>
							@if($event->place->lat != 0 && $event->place->lng !=0  )
								<a target="_blank" href="https://www.google.com/maps/search/?api=1&query={{ $event->place->lat }},{{ $event->place->lng }}">
									{{ __('Show on map') }}
								</a>
							@endif
						</dd>
					</dl>
					<div class="row">
						@if($event->isNotFinishYet())
							<div class="col-4 col-md-2">
								@auth
									@can('join-event', $event)
										<form action="{{ route('events.join', $event) }}" method="post">
											@csrf
											<button type="submit" class="btn btn-primary mt-2">
												{{ __('Join now!') }}
											</button>
										</form>
									@endif
									@can('leave-event', $event)
										<form action="{{ route('events.leave', $event) }}" method="post">
											@csrf
											<button type="submit" class="btn btn-primary mt-2">
												{{ __('Leave this event') }}
											</button>
										</form>
									@endcan
								@else
									<form action="{{ route('events.join', $event) }}" method="post">
										@csrf
										<button type="submit" class="btn btn-primary mt-2">
											{{ __('Login to join!') }}
										</button>
									</form>
								@endauth
							</div>
							<div class="col-8 col-md-4">
								{{ __('Joining is available until') }}<br>
								<strong>{{ $event->f_register_deadline }}</strong>
							</div>
						@else
							<div class="col-4 col-md-2">
								<button type="submit" class="btn btn-secendary" disabled>
									{{ __('This event is already over') }}
								</button>
							</div>
						@endif
						</div>
				</div>
			</div>
		</div>
	</section>
@endsection