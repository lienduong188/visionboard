<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    containerWidth: {
        type: Number,
        default: 900,
    },
    containerHeight: {
        type: Number,
        default: 900,
    },
});

// 100 positive words for waterfall effect
const systemPositiveWords = [
    // Thành công & Chiến thắng
    { word: 'Thành công', color: '#10B981' },
    { word: 'Chiến thắng', color: '#059669' },
    { word: 'Vinh quang', color: '#047857' },
    { word: 'Vô địch', color: '#065F46' },
    { word: 'Xuất sắc', color: '#10B981' },
    { word: 'Hoàn thành', color: '#34D399' },
    { word: 'Đạt được', color: '#6EE7B7' },
    { word: 'Tuyệt vời', color: '#059669' },
    { word: 'Phi thường', color: '#047857' },
    { word: 'Vĩ đại', color: '#065F46' },

    // Tăng trưởng & Tiến bộ
    { word: 'Phát triển', color: '#8B5CF6' },
    { word: 'Tiến bộ', color: '#7C3AED' },
    { word: 'Thay đổi', color: '#6D28D9' },
    { word: 'Bứt phá', color: '#5B21B6' },
    { word: 'Cải thiện', color: '#8B5CF6' },
    { word: 'Nở rộ', color: '#A78BFA' },
    { word: 'Thịnh vượng', color: '#C4B5FD' },
    { word: 'Mở rộng', color: '#7C3AED' },
    { word: 'Trưởng thành', color: '#6D28D9' },
    { word: 'Vươn lên', color: '#5B21B6' },

    // Động lực & Năng lượng
    { word: 'Động lực', color: '#F59E0B' },
    { word: 'Năng lượng', color: '#D97706' },
    { word: 'Đam mê', color: '#B45309' },
    { word: 'Nhiệt huyết', color: '#92400E' },
    { word: 'Khát vọng', color: '#F59E0B' },
    { word: 'Quyết tâm', color: '#FBBF24' },
    { word: 'Kiên trì', color: '#FCD34D' },
    { word: 'Cống hiến', color: '#D97706' },
    { word: 'Cam kết', color: '#B45309' },
    { word: 'Tập trung', color: '#92400E' },

    // Niềm vui & Hạnh phúc
    { word: 'Hạnh phúc', color: '#EC4899' },
    { word: 'Vui vẻ', color: '#DB2777' },
    { word: 'Bình an', color: '#BE185D' },
    { word: 'Thích thú', color: '#9D174D' },
    { word: 'Rạng rỡ', color: '#EC4899' },
    { word: 'Tươi sáng', color: '#F472B6' },
    { word: 'Sống động', color: '#F9A8D4' },
    { word: 'Phấn chấn', color: '#DB2777' },
    { word: 'Hân hoan', color: '#BE185D' },
    { word: 'Mãn nguyện', color: '#9D174D' },

    // Sức mạnh & Dũng cảm
    { word: 'Mạnh mẽ', color: '#EF4444' },
    { word: 'Sức mạnh', color: '#DC2626' },
    { word: 'Dũng mãnh', color: '#B91C1C' },
    { word: 'Quyết liệt', color: '#991B1B' },
    { word: 'Táo bạo', color: '#EF4444' },
    { word: 'Dũng cảm', color: '#F87171' },
    { word: 'Bất khuất', color: '#FCA5A5' },
    { word: 'Can đảm', color: '#DC2626' },
    { word: 'Kiên cường', color: '#B91C1C' },
    { word: 'Vững vàng', color: '#991B1B' },

    // Bình yên & Tĩnh tâm
    { word: 'Thanh thản', color: '#06B6D4' },
    { word: 'Tĩnh lặng', color: '#0891B2' },
    { word: 'Yên bình', color: '#0E7490' },
    { word: 'Hòa hợp', color: '#155E75' },
    { word: 'Cân bằng', color: '#06B6D4' },
    { word: 'Điềm tĩnh', color: '#22D3EE' },
    { word: 'Chánh niệm', color: '#67E8F9' },
    { word: 'Nhẹ nhàng', color: '#0891B2' },
    { word: 'Thư thái', color: '#0E7490' },
    { word: 'Dịu dàng', color: '#155E75' },

    // Tình yêu & Kết nối
    { word: 'Yêu thương', color: '#F43F5E' },
    { word: 'Tử tế', color: '#E11D48' },
    { word: 'Đồng cảm', color: '#BE123C' },
    { word: 'Quan tâm', color: '#9F1239' },
    { word: 'Ấm áp', color: '#F43F5E' },
    { word: 'Dịu hiền', color: '#FB7185' },
    { word: 'Nhân ái', color: '#FDA4AF' },
    { word: 'Thấu cảm', color: '#E11D48' },
    { word: 'Đoàn kết', color: '#BE123C' },
    { word: 'Gắn kết', color: '#9F1239' },

    // Sáng tạo & Cảm hứng
    { word: 'Sáng tạo', color: '#6366F1' },
    { word: 'Cảm hứng', color: '#4F46E5' },
    { word: 'Tưởng tượng', color: '#4338CA' },
    { word: 'Ước mơ', color: '#3730A3' },
    { word: 'Đổi mới', color: '#6366F1' },
    { word: 'Tài năng', color: '#818CF8' },
    { word: 'Xuất chúng', color: '#A5B4FC' },
    { word: 'Tầm nhìn', color: '#4F46E5' },
    { word: 'Kỳ diệu', color: '#4338CA' },
    { word: 'Huyền diệu', color: '#3730A3' },

    // Tự do & Khám phá
    { word: 'Tự do', color: '#14B8A6' },
    { word: 'Phiêu lưu', color: '#0D9488' },
    { word: 'Khám phá', color: '#0F766E' },
    { word: 'Tìm kiếm', color: '#115E59' },
    { word: 'Hành trình', color: '#14B8A6' },
    { word: 'Rong ruổi', color: '#2DD4BF' },
    { word: 'Bay cao', color: '#5EEAD4' },
    { word: 'Vươn tới', color: '#0D9488' },
    { word: 'Tiến về phía trước', color: '#0F766E' },
    { word: 'Chinh phục', color: '#115E59' },

    // Trí tuệ & Kiến thức
    { word: 'Trí tuệ', color: '#84CC16' },
    { word: 'Kiến thức', color: '#65A30D' },
    { word: 'Học hỏi', color: '#4D7C0F' },
    { word: 'Trưởng thành', color: '#3F6212' },
    { word: 'Khai sáng', color: '#84CC16' },
    { word: 'Nhận thức', color: '#A3E635' },
    { word: 'Minh bạch', color: '#BEF264' },
    { word: 'Chân lý', color: '#65A30D' },
    { word: 'Hiểu biết', color: '#4D7C0F' },
    { word: 'Tỉnh thức', color: '#3F6212' },
];

const fallingWords = ref([]);
let animationId = null;
let lastSpawnTime = 0;
const spawnInterval = 1200; // Spawn every 1.2 seconds
const maxWords = 20; // Max words on screen

// Spawn a new falling word
const spawnWord = () => {
    if (systemPositiveWords.length === 0) return;
    if (fallingWords.value.length >= maxWords) return;

    const randomWord = systemPositiveWords[Math.floor(Math.random() * systemPositiveWords.length)];
    const word = {
        id: Date.now() + Math.random(),
        ...randomWord,
        x: Math.random() * (props.containerWidth - 100) + 50,
        y: -50,
        speed: 0.3 + Math.random() * 0.7,
        opacity: 0.4 + Math.random() * 0.6,
        scale: 0.7 + Math.random() * 0.5,
        rotation: (Math.random() - 0.5) * 20,
    };

    fallingWords.value.push(word);
};

// Animation loop
const animate = (timestamp) => {
    // Spawn new words periodically
    if (timestamp - lastSpawnTime > spawnInterval) {
        spawnWord();
        lastSpawnTime = timestamp;
    }

    // Update positions
    fallingWords.value = fallingWords.value
        .map(word => ({
            ...word,
            y: word.y + word.speed,
            opacity: word.y > props.containerHeight * 0.7
                ? Math.max(0, word.opacity - 0.02)
                : word.opacity,
        }))
        .filter(word => word.y < props.containerHeight + 50 && word.opacity > 0);

    animationId = requestAnimationFrame(animate);
};

onMounted(() => {
    // Spawn initial words scattered across the screen
    for (let i = 0; i < 8; i++) {
        setTimeout(() => {
            const randomWord = systemPositiveWords[Math.floor(Math.random() * systemPositiveWords.length)];
            fallingWords.value.push({
                id: Date.now() + Math.random(),
                ...randomWord,
                x: Math.random() * (props.containerWidth - 100) + 50,
                y: Math.random() * props.containerHeight * 0.5,
                speed: 0.3 + Math.random() * 0.7,
                opacity: 0.4 + Math.random() * 0.6,
                scale: 0.7 + Math.random() * 0.5,
                rotation: (Math.random() - 0.5) * 20,
            });
        }, i * 150);
    }
    animationId = requestAnimationFrame(animate);
});

onUnmounted(() => {
    if (animationId) {
        cancelAnimationFrame(animationId);
    }
});
</script>

<template>
    <div class="absolute inset-0 overflow-hidden pointer-events-none z-20">
        <div
            v-for="word in fallingWords"
            :key="word.id"
            class="absolute waterfall-word"
            :style="{
                left: word.x + 'px',
                top: word.y + 'px',
                opacity: word.opacity,
                transform: `scale(${word.scale}) rotate(${word.rotation}deg)`,
            }"
        >
            <span
                class="px-4 py-2 rounded-full font-bold text-base whitespace-nowrap shadow-md"
                :style="{
                    backgroundColor: word.color + '40',
                    color: word.color,
                    textShadow: `0 1px 2px rgba(0,0,0,0.3)`,
                    border: `1px solid ${word.color}50`,
                    backdropFilter: 'blur(4px)',
                }"
            >
                {{ word.word }}
            </span>
        </div>
    </div>
</template>

<style scoped>
.waterfall-word {
    transition: opacity 0.3s ease;
}
</style>
