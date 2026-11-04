<x-mail::message>
# New customer message

Reference: **{{ $submission->id }}**

**From:** {{ $submission->name }} ({{ $submission->email }})  
@if($submission->phone)
**Phone:** {{ $submission->phone }}  
@endif
@if($submission->subject)
**Subject:** {{ $submission->subject }}
@endif

---

{{ $submission->message }}

This message was submitted through the Glamrush storefront contact form.
</x-mail::message>
