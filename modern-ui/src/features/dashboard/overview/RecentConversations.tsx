import { User } from "lucide-react";
import type { OverviewResponse } from "../type";
import { cn } from "@/lib/utils";
import { ScrollArea } from "@/components/ui/scroll-area";

export const ConversationList = ({
    conversations,
    className,
}: {
    conversations: OverviewResponse["data"]["messages"];
    className?: string;
}) => {
    const formatDate = (dateString: string) => {
        const date = new Date(dateString);
        const now = new Date();
        const diff = now.getTime() - date.getTime();
        const minutes = Math.floor(diff / 60000);
        const hours = Math.floor(diff / 3600000);

        if (minutes < 60) {
            return `${minutes} min`;
        } else if (hours < 24) {
            return `${hours}:${String(date.getMinutes()).padStart(2, "0")} ${
                date.getHours() >= 12 ? "PM" : "AM"
            }`;
        } else {
            return date.toLocaleDateString();
        }
    };

    const truncateText = (text: string, maxLength = 60) => {
        if (text.length <= maxLength) return text;
        return text.substring(0, maxLength) + "...";
    };

    const getStatusColor = (status: string) => {
        switch (status?.toLowerCase()) {
            case "new":
                return "bg-blue-500";
            case "read":
                return "bg-gray-400";
            case "urgent":
                return "bg-red-500";
            default:
                return "bg-blue-500";
        }
    };

    return (
        <div
            className={cn(
                `bg-background rounded-lg shadow-sm border mt-3`,
                className
            )}
        >
            {/* Header */}
            <div className="p-4 border-b">
                <h2 className="text-lg font-semibold ">Recent Conversations</h2>
            </div>

            {/* Conversation List */}
            <ScrollArea className="h-[300px] scroll-smooth w-sm">
                <div className="divide-y divide-border-100">
                    {conversations?.map((conversation) => (
                        <div
                            key={conversation.id}
                            className="p-4 flex items-start space-x-3 hover:bg-secondary cursor-pointer transition-colors"
                        >
                            {/* Avatar */}
                            <div className="flex-shrink-0">
                                <div className="w-10 h-10 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full flex items-center justify-center text-white font-medium text-sm">
                                    {conversation.sender?.name ? (
                                        conversation.sender.name
                                            .split(" ")
                                            .map((n) => n[0])
                                            .join("")
                                            .toUpperCase()
                                    ) : (
                                        <User size={16} />
                                    )}
                                </div>
                            </div>

                            {/* Content */}
                            <div className="flex-1 min-w-0">
                                <div className="flex items-center justify-between mb-1">
                                    <h3 className="text-sm font-medium truncate">
                                        {conversation.sender?.name ||
                                            "Unknown Sender"}
                                    </h3>
                                    <div className="flex items-center space-x-2">
                                        {conversation.status === "New" && (
                                            <div
                                                className={`w-2 h-2 rounded-full ${getStatusColor(
                                                    conversation.status
                                                )}`}
                                            />
                                        )}
                                        <span className="text-xs text-gray-500">
                                            {formatDate(conversation.date)}
                                        </span>
                                    </div>
                                </div>

                                <p className="text-sm text-gray-500 mb-1 font-medium">
                                    {conversation.title}
                                </p>

                                <p className="text-xs text-gray-500 line-clamp-2">
                                    {truncateText(conversation.body)}
                                </p>
                            </div>
                        </div>
                    ))}
                </div>
            </ScrollArea>

            {/* Empty State */}
            {conversations.length === 0 && (
                <div className="p-8 text-center text-gray-500">
                    <User className="mx-auto h-12 w-12 text-gray-300 mb-4" />
                    <p className="text-sm">No conversations yet</p>
                </div>
            )}
        </div>
    );
};

export default ConversationList;