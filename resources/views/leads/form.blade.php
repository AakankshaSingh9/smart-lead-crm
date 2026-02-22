@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto pb-12">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-slate-800 tracking-tight">{{ $title }}</h2>
        <p class="text-sm text-slate-500 mt-1">Fill in the details below to manage your lead record.</p>
    </div>

    <form method="POST" action="{{ $action }}" class="space-y-6">
        @csrf
        @if ($method !== 'POST')
            @method($method)
        @endif

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h3 class="font-semibold text-slate-700 flex items-center">
                    <i class="ph ph-user-circle mr-2 text-indigo-500 text-lg"></i> Contact Details
                </h3>
            </div>

            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-1">
                    <label class="block text-sm font-semibold text-slate-700">Name <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                            <i class="ph ph-user"></i>
                        </span>
                        <input type="text" name="name" value="{{ old('name', $lead->name) }}"
                            class="block w-full pl-10 pr-3 py-2.5 bg-white border border-slate-300 rounded-lg text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"
                            placeholder="John Doe" required>
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="block text-sm font-semibold text-slate-700">Email Address</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                            <i class="ph ph-envelope"></i>
                        </span>
                        <input type="email" name="email" value="{{ old('email', $lead->email) }}"
                            class="block w-full pl-10 pr-3 py-2.5 bg-white border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"
                            placeholder="john@example.com">
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="block text-sm font-semibold text-slate-700">Phone Number</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                            <i class="ph ph-phone"></i>
                        </span>
                        <input type="text" name="phone" value="{{ old('phone', $lead->phone) }}"
                            class="block w-full pl-10 pr-3 py-2.5 bg-white border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"
                            placeholder="+1 (555) 000-0000">
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="block text-sm font-semibold text-slate-700">Lead Source</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                            <i class="ph ph-megaphone"></i>
                        </span>
                        <input type="text" name="source" value="{{ old('source', $lead->source) }}"
                            class="block w-full pl-10 pr-3 py-2.5 bg-white border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"
                            placeholder="e.g. Website, LinkedIn">
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h3 class="font-semibold text-slate-700 flex items-center">
                    <i class="ph ph-funnel mr-2 text-indigo-500 text-lg"></i> Lead Management
                </h3>
            </div>

            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-1">
                    <label class="block text-sm font-semibold text-slate-700">Current Status <span class="text-red-500">*</span></label>
                    <select name="status" class="block w-full px-3 py-2.5 bg-white border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all appearance-none cursor-pointer" required>
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected(old('status', $lead->status ?: 'new') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-1">
                    <label class="block text-sm font-semibold text-slate-700">Next Follow-up Date</label>
                    <input type="date" name="follow_up_date" value="{{ old('follow_up_date', optional($lead->follow_up_date)->format('Y-m-d')) }}"
                        class="block w-full px-3 py-2.5 bg-white border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                </div>

                @if (auth()->user()->isAdmin())
                    <div class="md:col-span-2 space-y-1">
                        <label class="block text-sm font-semibold text-slate-700">Assign To Executive</label>
                        <select name="assigned_user_id" class="block w-full px-3 py-2.5 bg-white border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all cursor-pointer">
                            <option value="">Unassigned</option>
                            @foreach ($salesExecutives as $sales)
                                <option value="{{ $sales->id }}" @selected((string)old('assigned_user_id', $lead->assigned_user_id) === (string)$sales->id)>{{ $sales->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <div class="space-y-1">
                <label class="block text-sm font-semibold text-slate-700">Internal Notes</label>
                <textarea name="notes" rows="4"
                    class="block w-full px-3 py-2.5 bg-white border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"
                    placeholder="Add any specific requirements or call summaries here...">{{ old('notes', $lead->notes) }}</textarea>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
            <a href="{{ route('leads.index') }}"
               class="px-6 py-2.5 text-sm font-medium text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                Cancel
            </a>
            <button type="submit"
                class="px-8 py-2.5 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-500/30 shadow-lg shadow-indigo-200 transition-all">
                Save Lead
            </button>
        </div>
    </form>
</div>
@endsection
