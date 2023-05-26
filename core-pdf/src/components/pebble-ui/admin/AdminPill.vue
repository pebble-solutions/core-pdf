<template>
    <span class="badge rounded-pill text-bg-admin"><i class="bi bi-shield-shaded"></i> {{type}}</span>
</template>

<script>

export default {
    props: {
        type: {
            type: Number,
            default: 4
        }
    }
}

</script>