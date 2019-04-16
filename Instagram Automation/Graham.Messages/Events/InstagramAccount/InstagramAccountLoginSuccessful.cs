using NServiceBus;

namespace Graham.Messages.Events.InstagramAccount
{
    public class InstagramAccountLoginSuccessful : IEvent
    {
        public long InstagramAccountId { get; set; }
    }
}
