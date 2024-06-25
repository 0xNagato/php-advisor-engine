@props(['testimonial'])
<div {{ $attributes->class(['text-center relative px-[4px] py-[0] h-auto']) }}>
    <div class="rounded-[5px] border-[0.3px] border-[solid] border-[#000] bg-[#FFF] pt-[37px] px-[14px] pb-[26px] rounded-[5px]">
        <p class="text-[16px] font-light leading-[normal] text-left">
            {{ $testimonial['quote'] }}
        </p>

        <p class="text-[16px] font-normal leading-[normal] pt-[49px]">
            {{ $testimonial['author'] }}
        </p>
        <p class="pt-[14px] text-[#DE6520] text-[20.193px] leading-[normal]">
            @for ($i = 0; $i < 5; $i++)
                ★
            @endfor
        </p>
    </div>
</div>
