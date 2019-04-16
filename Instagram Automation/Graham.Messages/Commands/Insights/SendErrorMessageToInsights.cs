using NServiceBus;

namespace Graham.Messages.Commands.Insights
{
    public class SendErrorMessageToInsights : ICommand
    {
        public string ErrorMessage { get; set; }
    }
}
