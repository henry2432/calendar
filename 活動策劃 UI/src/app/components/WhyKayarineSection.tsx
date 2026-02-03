import { 
  Shield, 
  Settings, 
  Calendar, 
  MapPin, 
  Sunrise, 
  Star,
  Users,
  Camera,
  Instagram,
  TrendingUp
} from "lucide-react";
import mascotImage from "figma:asset/56488f0d023b323a4f3f29ab88b0e1776d92fe17.png";
import photographerMascot from "figma:asset/88b7509a2d132af1ba093c9cecce21c9e8b486c2.png";

const advantages = [
  {
    icon: Shield,
    title: "專業教練 & 安全第一",
    description: "經驗豐富教練團隊，全程安全保障"
  },
  {
    icon: Settings,
    title: "客製化團體行程",
    description: "人數、時間、難度、主題靈活調整"
  },
  {
    icon: Calendar,
    title: "彈性改期 & 天氣保障",
    description: "免費改期服務，天氣不適全額退款"
  }
];

const stats = [
  {
    icon: Users,
    number: "50+",
    label: "合作機構團體"
  },
  {
    icon: Users,
    number: "1000+",
    label: "服務客戶"
  },
  {
    icon: Star,
    number: "4.9",
    label: "Google 評分"
  }
];

export function WhyKayarineSection() {
  return (
    <section className="py-16 md:py-24 bg-white">
      <div className="container mx-auto px-4">
        <div className="text-center mb-12 relative">
          {/* Mascot with Speech Bubble */}
          <div className="flex justify-center items-center mb-8">
            <div className="relative">
              <img 
                src={mascotImage} 
                alt="Kayarine Mascot" 
                className="w-80 h-80 xl:w-96 xl:h-96"
              />
              {/* Speech Bubble - Above mascot */}
              <div className="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-full mb-4 bg-white rounded-2xl px-6 py-3 shadow-lg border-2 border-orange-500">
                <p className="text-lg font-medium text-gray-800 whitespace-nowrap">
                  為什麼選擇 Kayarine？
                </p>
                {/* Triangle pointer - Pointing down */}
                <div className="absolute left-1/2 -translate-x-1/2 -bottom-3 w-0 h-0 border-l-[12px] border-l-transparent border-t-[12px] border-t-orange-500 border-r-[12px] border-r-transparent"></div>
                <div className="absolute left-1/2 -translate-x-1/2 -bottom-2 w-0 h-0 border-l-[10px] border-l-transparent border-t-[10px] border-t-white border-r-[10px] border-r-transparent"></div>
              </div>
            </div>
          </div>
        </div>

        {/* Stats - Below Mascot */}
        <div className="mb-16">
          <div className="max-w-4xl mx-auto p-8 rounded-xl border-2 border-orange-200 bg-gradient-to-b from-white to-orange-50">
            <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
              {stats.map((stat, index) => {
                const Icon = stat.icon;
                return (
                  <div 
                    key={index}
                    className="flex flex-col items-center text-center"
                  >
                    <Icon className="h-10 w-10 mb-3 text-orange-500" />
                    <div className="text-4xl md:text-5xl mb-2 text-gray-800">
                      {stat.number}
                    </div>
                    <div className="text-lg text-gray-600">{stat.label}</div>
                  </div>
                );
              })}
            </div>
          </div>
        </div>

        {/* Advantages Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-16">
          {advantages.map((advantage, index) => {
            const Icon = advantage.icon;
            return (
              <div 
                key={index}
                className="flex flex-col items-center text-center p-6 rounded-lg hover:bg-gray-50 transition-colors duration-300"
              >
                <div className="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mb-4">
                  <Icon className="h-8 w-8 text-orange-500" />
                </div>
                <h3 className="text-xl mb-2">{advantage.title}</h3>
                <p className="text-gray-600">{advantage.description}</p>
              </div>
            );
          })}
        </div>

        {/* Section Divider - Photography Mascot with Speech Bubble */}
        <div className="text-center mt-16 mb-8">
          <div className="flex justify-center items-center mb-8">
            <div className="relative">
              <img 
                src={photographerMascot} 
                alt="Kayarine Photographer Mascot" 
                className="w-64 h-64 xl:w-80 xl:h-80"
              />
              {/* Speech Bubble - Above mascot */}
              <div className="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-full mb-4 bg-white rounded-2xl px-6 py-3 shadow-lg border-2 border-orange-500">
                <p className="text-lg font-medium text-gray-800 whitespace-nowrap">
                  需要活動記錄素材？
                </p>
                {/* Triangle pointer - Pointing down */}
                <div className="absolute left-1/2 -translate-x-1/2 -bottom-3 w-0 h-0 border-l-[12px] border-l-transparent border-t-[12px] border-t-orange-500 border-r-[12px] border-r-transparent"></div>
                <div className="absolute left-1/2 -translate-x-1/2 -bottom-2 w-0 h-0 border-l-[10px] border-l-transparent border-t-[10px] border-t-white border-r-[10px] border-r-transparent"></div>
              </div>
            </div>
          </div>
        </div>

        {/* Featured: Professional Photography Service */}
        <div className="mb-16 bg-gradient-to-br from-orange-50 to-amber-50 rounded-2xl p-8 md:p-12 border-2 border-orange-200">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
            <div>
              <div className="inline-flex items-center gap-2 bg-orange-500 text-white px-4 py-2 rounded-full mb-4">
                <Camera className="h-5 w-5" />
                <span className="font-medium">品牌價值提升</span>
              </div>
              <h3 className="text-2xl md:text-3xl mb-4">
                專業攝影服務
              </h3>
              <p className="text-lg text-gray-700 mb-6">
                專業攝影師 Drone 航拍，多角度記錄精彩瞬間
              </p>
              <div className="space-y-3">
                <div className="flex items-start gap-3">
                  <div className="w-6 h-6 bg-orange-500 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                    <span className="text-white text-sm">✓</span>
                  </div>
                  <p className="text-gray-700">專業器材提供高質素材，增加品牌活動彈性</p>
                </div>
                <div className="flex items-start gap-3">
                  <div className="w-6 h-6 bg-orange-500 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                    <span className="text-white text-sm">✓</span>
                  </div>
                  <p className="text-gray-700">航拍視角展現團隊氣勢，提升企業形象</p>
                </div>
                <div className="flex items-start gap-3">
                  <div className="w-6 h-6 bg-orange-500 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                    <span className="text-white text-sm">✓</span>
                  </div>
                  <p className="text-gray-700">即時分享社交媒體，擴大活動影響力</p>
                </div>
              </div>
            </div>
            <div className="bg-white rounded-xl p-6 shadow-lg">
              <h4 className="text-xl mb-4 text-center">我們的社交媒體影響力</h4>
              <div className="space-y-4">
                <div className="flex items-center justify-between p-4 bg-gradient-to-r from-purple-50 to-pink-50 rounded-lg">
                  <div className="flex items-center gap-3">
                    <Instagram className="h-8 w-8 text-pink-600" />
                    <div>
                      <p className="text-sm text-gray-600">Instagram 追蹤者</p>
                      <p className="text-2xl">1.8萬+</p>
                    </div>
                  </div>
                </div>
                <div className="flex items-center justify-between p-4 bg-gradient-to-r from-orange-50 to-red-50 rounded-lg">
                  <div className="flex items-center gap-3">
                    <TrendingUp className="h-8 w-8 text-orange-600" />
                    <div>
                      <p className="text-sm text-gray-600">單篇最高流量</p>
                      <p className="text-2xl">20萬+</p>
                    </div>
                  </div>
                </div>
              </div>
              <p className="text-sm text-gray-600 text-center mt-4">
                您的活動將獲得專業曝光，觸及更廣泛受眾
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}