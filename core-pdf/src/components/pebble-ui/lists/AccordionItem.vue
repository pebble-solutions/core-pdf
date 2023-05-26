<template>
    <div class="accordion-item">
        <h2 class="accordion-header" :id="'heading'+id">
            <button class="accordion-button"
                    :class="{'collapsed': !show}"
                    type="button" 
                    data-bs-toggle="collapse" 
                    :data-bs-target="'#collapse'+id" 
                    aria-expanded="false" 
                    :aria-controls="'collapse'+id">
                <slot name="header"></slot>
            </button>
        </h2>

        <div :id="'collapse'+id" class="accordion-collapse collapse" :class="{'show': show}" :aria-labelledby="'heading'+id" data-bs-parent="#accordionExample">
            <div class="accordion-item">
                <slot name="body"></slot>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        id: Number,
        show: Boolean,
    },
}
</script>