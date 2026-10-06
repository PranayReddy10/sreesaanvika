<x-mail::message>
# {{ $enquiry->name }} wrote in

{{ $enquiry->message }}

**Reach them**

{{ $enquiry->email }}
@if ($enquiry->phone){{ $enquiry->phone }}@endif
@if ($enquiry->order_number)About order {{ $enquiry->order_number }}@endif

Press reply and they will get it — this email is addressed from them.

<x-mail::button :url="url('/admin/enquiries')">
Open it in the admin
</x-mail::button>
</x-mail::message>
