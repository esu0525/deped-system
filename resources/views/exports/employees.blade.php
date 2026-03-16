<table>
    <thead>
        <tr>
            <th style="font-weight: bold; background-color: #f1f5f9; text-align: center;">Full Name</th>
            <th style="font-weight: bold; background-color: #f1f5f9; text-align: center;">Position</th>
            <th style="font-weight: bold; background-color: #f1f5f9; text-align: center;">Department</th>
            <th style="font-weight: bold; background-color: #f1f5f9; text-align: center;">Employment Status</th>
            <th style="font-weight: bold; background-color: #f1f5f9; text-align: center;">Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach($employees as $employee)
            <tr>
                <td style="border: 1px solid #000000; padding: 5px;">{{ strtoupper($employee->full_name) }}</td>
                <td style="border: 1px solid #000000; padding: 5px;">{{ $employee->position }}</td>
                <td style="border: 1px solid #000000; padding: 5px;">{{ $employee->department->name ?? 'N/A' }}</td>
                <td style="border: 1px solid #000000; padding: 5px;">{{ $employee->employment_status }}</td>
                <td style="border: 1px solid #000000; padding: 5px;">{{ $employee->status }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
