using NServiceBus;

namespace Graham.Messages.Events.InstagramAccount
{
    public class InstagramAccountLoginFailed : IEvent
    {
        public long InstagramAccountId { get; set; }
    }
}
