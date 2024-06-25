@props(['testimonial'])
<div {{ $attributes->class(['text-center relative px-[4px] py-[0] flex-1']) }}>
    <div class="rounded-[5px] border-[0.3px] border-[solid] border-[#000] bg-[#FFF] pt-6 px-[14px] pb-[26px] rounded-[5px]">
        <p class="text-[16px] font-light leading-[normal] text-left">
            {{ $testimonial['quote'] }}
        </p>

        <p class="text-[16px] font-normal leading-[normal] pt-[49px]">
            {{ $testimonial['author'] }}
        </p>
    </div>
</div>
