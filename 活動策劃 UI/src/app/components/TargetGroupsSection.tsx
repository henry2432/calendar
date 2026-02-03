import { ArrowRight } from "lucide-react";
import { Button } from "@/app/components/ui/button";
import { Card, CardContent } from "@/app/components/ui/card";
import corporateImage from "figma:asset/036f75b3c737e26fa6837ae62db5d8973c6ee022.png";
import communityImage from "figma:asset/746b8c1161795709db06a79e8752172c7052d95b.png";

const targetGroups = [
  {
    title: "公司 / Corporate",
    titleEn: "團隊建設專家",
    description: "提升團隊凝聚力、領導力與壓力釋放 – 客製化 Fun Day / Annual Outing，建立品牌形象",
    image: corporateImage,
    alt: "Corporate team building",
    highlights: ["Team Building", "Fun Day", "年度活動", "品牌活動"]
  },
  {
    title: "學校 / Schools",
    titleEn: "戶外教育課程",
    description: "戶外教育課程、冒險體驗、證書課程（直立板入門/中級）",
    image: "https://images.unsplash.com/photo-1766818981351-af3c479803de?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxoYXBweSUyMGNoaWxkcmVuJTIwcGxheWluZyUyMHdhdGVyJTIwYmVhY2glMjBncm91cHxlbnwxfHx8fDE3NzAwNTQzMjR8MA&ixlib=rb-4.1.0&q=80&w=1080&utm_source=figma&utm_medium=referral",
    alt: "Students kayaking",
    highlights: ["戶外教育", "冒險體驗", "證書課程", "團體訓練"]
  },
  {
    title: "社區中心 / Community",
    titleEn: "社區凝聚活動",
    description: "社區凝聚活動、親子/長者友善行程、沙灘清潔結合水上體驗",
    image: communityImage,
    alt: "Community beach activity",
    highlights: ["親子活動", "長者友善", "社區活動", "環保體驗"]
  }
];

export function TargetGroupsSection() {
  return (
    <section className="py-16 md:py-24 bg-gray-50">
      <div className="container mx-auto px-4">
        <div className="text-center mb-12">
          <h2 className="text-3xl md:text-4xl lg:text-5xl mb-4">
            我們服務的對象
          </h2>
          <p className="text-lg md:text-xl text-gray-600 max-w-2xl mx-auto">
            專業定制方案，滿足不同團體需求
          </p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
          {targetGroups.map((group, index) => (
            <Card 
              key={index} 
              className="overflow-hidden hover:shadow-xl transition-shadow duration-300 border-none"
            >
              <div className="relative h-64 overflow-hidden">
                <img
                  src={group.image}
                  alt={group.alt}
                  className="w-full h-full object-cover hover:scale-105 transition-transform duration-300"
                />
                <div className="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
                <div className="absolute bottom-4 left-4 right-4 text-white">
                  <h3 className="text-2xl mb-1">{group.title}</h3>
                  <p className="text-sm opacity-90">{group.titleEn}</p>
                </div>
              </div>
              
              <CardContent className="p-6">
                <p className="text-gray-700 mb-4 min-h-[60px]">
                  {group.description}
                </p>
                
                <div className="flex flex-wrap gap-2 mb-4">
                  {group.highlights.map((highlight, idx) => (
                    <span 
                      key={idx}
                      className="text-xs px-3 py-1 bg-orange-50 text-orange-600 rounded-full"
                    >
                      {highlight}
                    </span>
                  ))}
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      </div>
    </section>
  );
}