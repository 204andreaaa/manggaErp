{{--
  Two-column "label | value | label | value" field grid, Salesforce style.
  $left / $right: arrays of [label, value] where value is already-escaped HTML
  (wrap plain text with e() at the call site).
--}}
<table class="sf-fields">
  <tbody>
    @for($i = 0; $i < max(count($left), count($right)); $i++)
      <tr>
        <td class="sf-label">{{ $left[$i][0] ?? '' }}</td>
        <td class="sf-value">{!! $left[$i][1] ?? '' !!}</td>
        <td class="sf-label">{{ $right[$i][0] ?? '' }}</td>
        <td class="sf-value">{!! $right[$i][1] ?? '' !!}</td>
      </tr>
    @endfor
  </tbody>
</table>
