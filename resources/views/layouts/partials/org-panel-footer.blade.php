<div class="mt-auto border-t border-white/10 pt-3 space-y-1 px-1">
    @orgCan('category.view')
        <a href="{{ route('workspace.categories.index') }}"
           class="flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-medium text-white/80 hover:bg-white/10 hover:text-white">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
            </svg>
            <span data-sidebar-label>Categories</span>
        </a>
    @endorgCan
    @orgCan('member.view')
        <a href="{{ route('org-admin.members.index') }}"
           class="flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-medium text-white/80 hover:bg-white/10 hover:text-white">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1z"/>
            </svg>
            <span data-sidebar-label>Members</span>
        </a>
    @endorgCan
</div>
