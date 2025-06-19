<x-filament-panels::page>
    <div class="space-y-6">
        @if($this->showAttendanceTable && !empty($this->attendanceData))
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-900">
                            Attendance Report for {{ \Carbon\Carbon::createFromFormat('m', $this->selectedMonth)->format('F') }} {{ $this->selectedYear }}
                        </h3>
                        <div class="text-sm text-gray-500">
                            Page {{ $this->currentPage }} of {{ $this->totalPages }} ({{ $this->totalEmployees }} employees)
                        </div>
                    </div>

                    @if($this->isEmployeeUser)
                        <div class="mt-2 flex items-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                <svg class="-ml-0.5 mr-1.5 h-2 w-2" fill="currentColor" viewBox="0 0 8 8">
                                    <circle cx="4" cy="4" r="3" />
                                </svg>
                                Viewing your own attendance
                            </span>
                        </div>
                    @elseif(!empty($this->selectedEmployees))
                        <div class="mt-2 flex items-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <svg class="-ml-0.5 mr-1.5 h-2 w-2" fill="currentColor" viewBox="0 0 8 8">
                                    <circle cx="4" cy="4" r="3" />
                                </svg>
                                Filtered: {{ count($this->selectedEmployees) }} selected employees
                            </span>
                        </div>
                    @endif
                </div>

                <div class="overflow-x-auto relative">
                    <table class="min-w-full divide-y divide-gray-200" style="position: relative;">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="sticky left-0 z-30 bg-gray-50 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r-2 border-gray-300 min-w-[200px]" style="position: sticky; left: 0; box-shadow: 2px 0 4px rgba(0,0,0,0.1);">
                                    Employee Name
                                </th>
                                @foreach($this->dates as $date)
                                    @php
                                        $dayOfWeek = \Carbon\Carbon::parse($date)->format('w');
                                        $isWeekend = in_array($dayOfWeek, [0, 6]); // 0 = Sunday, 6 = Saturday
                                        $headerBg = $isWeekend ? 'bg-red-50' : 'bg-gray-50';
                                    @endphp
                                    <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider min-w-[80px] {{ $headerBg }}">
                                        <div class="space-y-1">
                                            <div class="{{ $isWeekend ? 'text-red-600 font-semibold' : '' }}">{{ \Carbon\Carbon::parse($date)->format('d') }}</div>
                                            <div class="text-xs {{ $isWeekend ? 'text-red-500' : '' }}">{{ \Carbon\Carbon::parse($date)->format('D') }}</div>
                                        </div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($this->attendanceData as $employeeId => $employeeData)
                                <tr class="hover:bg-gray-50">
                                    <td class="sticky left-0 z-30 bg-white px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 border-r-2 border-gray-300 min-w-[200px]" style="position: sticky; left: 0; box-shadow: 2px 0 4px rgba(0,0,0,0.1);">
                                        {{ $employeeData['employee_name'] }}
                                    </td>
                                    @foreach($this->dates as $date)
                                        @php
                                            $attendanceType = $employeeData['attendance'][$date] ?? 'N/A';
                                            $dayOfWeek = \Carbon\Carbon::parse($date)->format('w');
                                            $isWeekend = in_array($dayOfWeek, [0, 6]);
                                            $cellBg = $isWeekend ? 'bg-red-25' : '';

                                            $bgColor = match($attendanceType) {
                                                'Present' => 'bg-green-100 text-green-800',
                                                'Absent' => 'bg-red-100 text-red-800',
                                                'Half Day' => 'bg-yellow-100 text-yellow-800',
                                                'Leave' => 'bg-blue-100 text-blue-800',
                                                'Holiday' => 'bg-purple-100 text-purple-800',
                                                'Weekend' => 'bg-gray-100 text-gray-800',
                                                default => 'bg-gray-50 text-gray-500',
                                            };
                                        @endphp
                                        <td class="px-3 py-4 whitespace-nowrap text-center text-xs {{ $cellBg }}">
                                            @if($attendanceType !== 'N/A')
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $bgColor }}">
                                                    {{ $attendanceType }}
                                                </span>
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Controls -->
                @if($this->totalPages > 1)
                    <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                        <div class="flex items-center">
                            <p class="text-sm text-gray-700">
                                Showing {{ (($this->currentPage - 1) * $this->perPage) + 1 }} to {{ min($this->currentPage * $this->perPage, $this->totalEmployees) }} of {{ $this->totalEmployees }} employees
                            </p>
                        </div>

                        <div class="flex items-center space-x-2">
                            <!-- Previous Button -->
                            <button
                                wire:click="previousPage"
                                @if($this->currentPage <= 1) disabled @endif
                                class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                Previous
                            </button>

                            <!-- Page Numbers -->
                            @php
                                $start = max(1, $this->currentPage - 2);
                                $end = min($this->totalPages, $this->currentPage + 2);
                            @endphp

                            @if($start > 1)
                                <button wire:click="goToPage(1)" class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">1</button>
                                @if($start > 2)
                                    <span class="text-gray-500">...</span>
                                @endif
                            @endif

                            @for($i = $start; $i <= $end; $i++)
                                <button
                                    wire:click="goToPage({{ $i }})"
                                    class="relative inline-flex items-center px-3 py-2 text-sm font-medium {{ $i == $this->currentPage ? 'text-white bg-blue-600 border-blue-600 font-semibold' : 'text-gray-700 bg-white border-gray-300 hover:bg-gray-50' }} border rounded-md">
                                    {{ $i }}
                                </button>
                            @endfor

                                                        @if($end < $this->totalPages)
                                @if($end < $this->totalPages - 1)
                                    <span class="text-gray-500">...</span>
                                @endif
                                <button wire:click="goToPage({{ $this->totalPages }})" class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">{{ $this->totalPages }}</button>
                            @endif

                            <!-- Next Button -->
                            <button
                                wire:click="nextPage"
                                @if($this->currentPage >= $this->totalPages) disabled @endif
                                class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                Next
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        @else
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900">No Attendance Data</h3>
                <p class="mt-2 text-sm text-gray-500">
                    Click the "View Monthly Attendance" button above to select a month and view attendance data.
                </p>
            </div>
        @endif
    </div>
</x-filament-panels::page>
